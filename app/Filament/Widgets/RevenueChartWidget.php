<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue (Last 30 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = collect();
        $revenues = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days->push($date->format('M j'));

            $revenue = Booking::whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->with('lineItems')
                ->get()
                ->sum(fn($booking) => $booking->lineItems->sum('total_cents'));

            $revenues->push($revenue / 100); // Convert to dollars
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenues->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
