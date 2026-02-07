<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Resource;
use Filament\Widgets\ChartWidget;

class PopularCourtsWidget extends ChartWidget
{
    protected ?string $heading = 'Most Popular Courts';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $courtBookings = Resource::withCount('reservations')
            ->orderBy('reservations_count', 'desc')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $courtBookings->pluck('reservations_count')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#8b5cf6',
                        '#ec4899',
                        '#f59e0b',
                        '#10b981',
                    ],
                ],
            ],
            'labels' => $courtBookings->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
