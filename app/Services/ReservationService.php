<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Reservation;
use Carbon\Carbon;

class ReservationService
{
    /**
     * Create a reservation for a booking.
     */
    public function createReservation(
        Booking $booking,
        Carbon $date,
        string $startTime,
        string $endTime
    ): Reservation {
        return Reservation::create([
            'booking_id' => $booking->id,
            'resource_id' => $booking->resource_id,
            'reservation_date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Check if there's a conflicting reservation.
     */
    public function hasConflict(
        int $resourceId,
        Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $query = Reservation::where('resource_id', $resourceId)
            ->where('reservation_date', $date->format('Y-m-d'))
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed']);
            })
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    // Existing reservation starts during new time
                    $q2->where('start_time', '>=', $startTime)
                        ->where('start_time', '<', $endTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // Existing reservation ends during new time
                    $q2->where('end_time', '>', $startTime)
                        ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // Existing reservation completely contains new time
                    $q2->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
            });

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Get reservations for a resource on a date.
     */
    public function getReservations(int $resourceId, Carbon $date)
    {
        return Reservation::where('resource_id', $resourceId)
            ->where('reservation_date', $date->format('Y-m-d'))
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed']);
            })
            ->with('booking.user')
            ->orderBy('start_time')
            ->get();
    }
}
