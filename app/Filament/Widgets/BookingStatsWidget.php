<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's bookings
        $todayBookings = Booking::whereDate('created_at', $today)->count();
        
        // This week's bookings
        $weekBookings = Booking::where('created_at', '>=', $thisWeek)->count();
        
        // This month's bookings
        $monthBookings = Booking::where('created_at', '>=', $thisMonth)->count();

        // Cancellation rate
        $totalBookings = Booking::count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        $cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 1) : 0;

        return [
            Stat::make('Today\'s Bookings', $todayBookings)
                ->description('Bookings created today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('This Week', $weekBookings)
                ->description('Bookings this week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('This Month', $monthBookings)
                ->description('Bookings this month')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Cancellation Rate', $cancellationRate . '%')
                ->description($cancelledBookings . ' of ' . $totalBookings . ' bookings')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancellationRate > 20 ? 'danger' : 'success'),
        ];
    }
}
