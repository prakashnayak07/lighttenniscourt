<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Reports extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.pages.reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public $startDate;

    public $endDate;

    public $bookingStats = [];

    public $revenueStats = [];

    public $utilizationStats = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadReports();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Date Range')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),

                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->required()
                            ->default(Carbon::now()->endOfMonth())
                            ->after('startDate'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function loadReports(): void
    {
        $reportService = app(ReportService::class);
        $from = Carbon::parse($this->startDate);
        $to = Carbon::parse($this->endDate);

        $this->bookingStats = $reportService->getBookingReport($from, $to);
        $this->revenueStats = $reportService->getRevenueReport($from, $to);
        $this->utilizationStats = $reportService->getCourtUtilizationReport($from, $to);
    }

    public function exportBookingsCsv(): void
    {
        $reportService = app(ReportService::class);
        $from = Carbon::parse($this->startDate);
        $to = Carbon::parse($this->endDate);

        $bookings = \App\Models\Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->with(['resource', 'user'])
            ->get()
            ->map(fn ($booking) => [
                'ID' => $booking->id,
                'Court' => $booking->resource->name,
                'User' => $booking->user->name,
                'Date' => $booking->date->format('Y-m-d'),
                'Time' => $booking->start_time . ' - ' . $booking->end_time,
                'Status' => $booking->status,
                'Payment Status' => $booking->payment_status,
                'Created At' => $booking->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();

        $filepath = $reportService->exportToCsv($bookings, 'bookings_' . now()->format('Y-m-d_His') . '.csv');

        $this->redirect(route('filament.admin.pages.reports'));
        
        // Note: In production, you'd want to download the file
        // For now, it's saved to storage/app/reports/
    }
}
