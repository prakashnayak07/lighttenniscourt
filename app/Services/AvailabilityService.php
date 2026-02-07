<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\Reservation;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get available time slots for a resource on a given date.
     */
    public function getAvailableSlots(
        Resource $resource,
        Carbon $date,
        ?int $durationMinutes = null
    ): Collection {
        $durationMinutes = $durationMinutes ?? $resource->time_block_minutes;

        // Generate all possible slots
        $allSlots = $this->generateTimeSlots($resource, $date, $durationMinutes);

        // Get booked and maintenance slots
        $bookedSlots = $this->getBookedSlots($resource, $date);
        $maintenanceSlots = $this->getMaintenanceSlots($resource, $date);

        // Filter out unavailable slots
        return $allSlots->filter(function ($slot) use ($bookedSlots, $maintenanceSlots) {
            return ! $this->isSlotConflicting($slot, $bookedSlots) &&
                   ! $this->isSlotConflicting($slot, $maintenanceSlots);
        });
    }

    /**
     * Generate all possible time slots for a resource.
     */
    protected function generateTimeSlots(
        Resource $resource,
        Carbon $date,
        int $durationMinutes
    ): Collection {
        $slots = collect();
        $start = Carbon::parse($date->format('Y-m-d').' '.$resource->daily_start_time);
        $end = Carbon::parse($date->format('Y-m-d').' '.$resource->daily_end_time);

        $current = $start->copy();

        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slots->push([
                'start_time' => $current->format('H:i:s'),
                'end_time' => $current->copy()->addMinutes($durationMinutes)->format('H:i:s'),
                'date' => $date->format('Y-m-d'),
            ]);

            $current->addMinutes($resource->time_block_minutes);
        }

        return $slots;
    }

    /**
     * Get booked time slots for a resource on a date.
     */
    protected function getBookedSlots(Resource $resource, Carbon $date): Collection
    {
        return Reservation::where('resource_id', $resource->id)
            ->where('reservation_date', $date->format('Y-m-d'))
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed']);
            })
            ->get()
            ->map(fn ($reservation) => [
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
            ]);
    }

    /**
     * Get maintenance time slots for a resource on a date.
     */
    protected function getMaintenanceSlots(Resource $resource, Carbon $date): Collection
    {
        return MaintenanceSchedule::where('resource_id', $resource->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->get()
            ->map(fn ($schedule) => [
                'start_time' => Carbon::parse($schedule->start_datetime)->format('H:i:s'),
                'end_time' => Carbon::parse($schedule->end_datetime)->format('H:i:s'),
            ]);
    }

    /**
     * Check if a slot conflicts with existing slots.
     */
    protected function isSlotConflicting(array $slot, Collection $existingSlots): bool
    {
        foreach ($existingSlots as $existing) {
            if ($this->timesOverlap(
                $slot['start_time'],
                $slot['end_time'],
                $existing['start_time'],
                $existing['end_time']
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a resource is available for a specific time range.
     */
    public function isAvailable(
        Resource $resource,
        Carbon $date,
        string $startTime,
        string $endTime
    ): bool {
        // Check if resource is enabled
        if ($resource->status !== 'enabled') {
            return false;
        }

        // Check if within operating hours
        if (! $this->isWithinOperatingHours($resource, $startTime, $endTime)) {
            return false;
        }

        // Check for existing bookings
        if ($this->hasConflictingBooking($resource, $date, $startTime, $endTime)) {
            return false;
        }

        // Check for maintenance
        if ($this->hasConflictingMaintenance($resource, $date, $startTime, $endTime)) {
            return false;
        }

        return true;
    }

    /**
     * Check if time is within operating hours.
     */
    protected function isWithinOperatingHours(Resource $resource, string $startTime, string $endTime): bool
    {
        return $startTime >= $resource->daily_start_time &&
               $endTime <= $resource->daily_end_time;
    }

    /**
     * Check if there's a conflicting booking.
     */
    protected function hasConflictingBooking(
        Resource $resource,
        Carbon $date,
        string $startTime,
        string $endTime
    ): bool {
        return Reservation::where('resource_id', $resource->id)
            ->where('reservation_date', $date->format('Y-m-d'))
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed']);
            })
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime) {
                    // New booking starts during existing booking
                    $q2->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q2) use ($endTime) {
                    // New booking ends during existing booking
                    $q2->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // New booking completely contains existing booking
                    $q2->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();
    }

    /**
     * Check if there's conflicting maintenance.
     */
    protected function hasConflictingMaintenance(
        Resource $resource,
        Carbon $date,
        string $startTime,
        string $endTime
    ): bool {
        $startDateTime = Carbon::parse($date->format('Y-m-d').' '.$startTime);
        $endDateTime = Carbon::parse($date->format('Y-m-d').' '.$endTime);

        return MaintenanceSchedule::where('resource_id', $resource->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->where(function ($q2) use ($startDateTime) {
                    $q2->where('start_datetime', '<=', $startDateTime)
                        ->where('end_datetime', '>', $startDateTime);
                })->orWhere(function ($q2) use ($endDateTime) {
                    $q2->where('start_datetime', '<', $endDateTime)
                        ->where('end_datetime', '>=', $endDateTime);
                })->orWhere(function ($q2) use ($startDateTime, $endDateTime) {
                    $q2->where('start_datetime', '>=', $startDateTime)
                        ->where('end_datetime', '<=', $endDateTime);
                });
            })
            ->exists();
    }

    /**
     * Check if two time ranges overlap.
     */
    protected function timesOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        return ($start1 < $end2) && ($end1 > $start2);
    }
}
