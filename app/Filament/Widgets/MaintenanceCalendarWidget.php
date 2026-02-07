<?php

namespace App\Filament\Widgets;

use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class MaintenanceCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.maintenance-calendar-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public $currentMonth;

    public $currentYear;

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function getCalendarData(): array
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get all maintenance schedules for this month
        $schedules = MaintenanceSchedule::with('resource')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_datetime', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_datetime', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where('start_datetime', '<=', $startOfMonth)
                            ->where('end_datetime', '>=', $endOfMonth);
                    });
            })
            ->get();

        // Build calendar grid
        $calendar = [];
        $currentDate = $startOfMonth->copy()->startOfWeek();
        $endDate = $endOfMonth->copy()->endOfWeek();

        while ($currentDate <= $endDate) {
            $weekDays = [];
            
            for ($i = 0; $i < 7; $i++) {
                $daySchedules = $schedules->filter(function ($schedule) use ($currentDate) {
                    $scheduleStart = Carbon::parse($schedule->start_datetime)->startOfDay();
                    $scheduleEnd = Carbon::parse($schedule->end_datetime)->endOfDay();
                    return $currentDate->between($scheduleStart, $scheduleEnd);
                });

                $weekDays[] = [
                    'date' => $currentDate->copy(),
                    'isCurrentMonth' => $currentDate->month === $this->currentMonth,
                    'isToday' => $currentDate->isToday(),
                    'schedules' => $daySchedules->map(fn($s) => [
                        'id' => $s->id,
                        'court' => $s->resource->name,
                        'type' => $s->type,
                        'status' => $s->status,
                        'time' => Carbon::parse($s->start_datetime)->format('H:i'),
                    ])->toArray(),
                ];

                $currentDate->addDay();
            }

            $calendar[] = $weekDays;
        }

        return $calendar;
    }

    public function getMonthName(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }
}
