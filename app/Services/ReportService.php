<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get booking report for date range.
     */
    public function getBookingReport(Carbon $from, Carbon $to): array
    {
        $bookings = \App\Models\Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->with(['resource', 'user'])
            ->get();

        return [
            'total_bookings' => $bookings->count(),
            'confirmed_bookings' => $bookings->where('status', 'confirmed')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            'bookings_by_court' => $bookings->groupBy('resource_id')->map(fn($group) => [
                'court_name' => $group->first()->resource->name,
                'count' => $group->count(),
            ])->values(),
        ];
    }

    /**
     * Get revenue report for date range.
     */
    public function getRevenueReport(Carbon $from, Carbon $to): array
    {
        $bookings = \App\Models\Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'paid')
            ->with('lineItems')
            ->get();

        $totalRevenue = $bookings->sum(fn($booking) => $booking->lineItems->sum('total_cents'));

        return [
            'total_revenue_cents' => $totalRevenue,
            'total_revenue' => '$' . number_format($totalRevenue / 100, 2),
            'paid_bookings' => $bookings->count(),
            'average_booking_value_cents' => $bookings->count() > 0 ? $totalRevenue / $bookings->count() : 0,
            'revenue_by_payment_method' => $bookings->groupBy('payment_method')->map(fn($group) => [
                'method' => $group->first()->payment_method,
                'count' => $group->count(),
                'total_cents' => $group->sum(fn($booking) => $booking->lineItems->sum('total_cents')),
            ])->values(),
        ];
    }

    /**
     * Get court utilization report.
     */
    public function getCourtUtilizationReport(Carbon $from, Carbon $to): array
    {
        $resources = Resource::with(['reservations' => function ($query) use ($from, $to) {
            $query->whereBetween('date', [$from, $to]);
        }])->get();

        $totalDays = $from->diffInDays($to) + 1;

        return $resources->map(function ($resource) use ($totalDays) {
            $totalReservations = $resource->reservations->count();
            $totalHours = $resource->reservations->sum(function ($reservation) {
                $start = Carbon::parse($reservation->start_time);
                $end = Carbon::parse($reservation->end_time);
                return $start->diffInHours($end);
            });

            // Assuming 12 hours of operation per day
            $availableHours = $totalDays * 12;
            $utilizationRate = $availableHours > 0 ? ($totalHours / $availableHours) * 100 : 0;

            return [
                'court_name' => $resource->name,
                'total_reservations' => $totalReservations,
                'total_hours_booked' => $totalHours,
                'utilization_rate' => round($utilizationRate, 2) . '%',
            ];
        })->toArray();
    }

    /**
     * Export data to CSV format.
     */
    public function exportToCsv(array $data, string $filename): string
    {
        $filepath = storage_path('app/reports/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        if (!empty($data)) {
            // Write headers
            fputcsv($file, array_keys($data[0]));

            // Write data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }

        fclose($file);

        return $filepath;
    }
}
