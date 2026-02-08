<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
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
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        return $schema
            ->schema([
                Section::make('Filters')
                    ->schema(array_filter([
                        $isSuperAdmin ? Select::make('organizationId')
                            ->label('Organization')
                            ->placeholder('All organizations')
                            ->options(Organization::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live() : null,
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->required()
                            ->default(Carbon::now()->endOfMonth())
                            ->after('startDate'),
                    ]))
                    ->columns($isSuperAdmin ? 3 : 2),
            ])
            ->statePath('data');
    }

    public function getReportOrganizationId(): ?int
    {
        $id = $this->data['organizationId'] ?? null;
        if ($id !== null && $id !== '') {
            return (int) $id;
        }
        if (! auth()->user()?->isSuperAdmin()) {
            return (int) (auth()->user()?->organization_id ?? 0) ?: null;
        }

        return null;
    }

    public function loadReports(): void
    {
        $reportService = app(ReportService::class);
        $from = Carbon::parse($this->data['startDate'] ?? $this->startDate);
        $to = Carbon::parse($this->data['endDate'] ?? $this->endDate);
        $organizationId = $this->getReportOrganizationId();

        $this->startDate = $from->format('Y-m-d');
        $this->endDate = $to->format('Y-m-d');

        $this->bookingStats = $reportService->getBookingReport($from, $to, $organizationId);
        $this->revenueStats = $reportService->getRevenueReport($from, $to, $organizationId);
        $this->utilizationStats = $reportService->getCourtUtilizationReport($from, $to, $organizationId);
    }

    public function exportBookingsCsv(): void
    {
        $reportService = app(ReportService::class);
        $from = Carbon::parse($this->data['startDate'] ?? $this->startDate);
        $to = Carbon::parse($this->data['endDate'] ?? $this->endDate);
        $organizationId = $this->getReportOrganizationId();

        $bookings = \App\Models\Booking::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereBetween('created_at', [$from, $to])
            ->with(['resource', 'user', 'reservations' => fn ($q) => $q->orderBy('id')])
            ->get()
            ->map(function ($booking) {
                $reservation = $booking->reservations->first();
                $user = $booking->user;
                $userLabel = $user ? trim($user->first_name.' '.$user->last_name) : '';
                if ($userLabel === '' && $user) {
                    $userLabel = $user->email;
                }

                return [
                    'ID' => $booking->id,
                    'Court' => $booking->resource?->name ?? '',
                    'User' => $userLabel,
                    'Date' => $reservation?->reservation_date?->format('Y-m-d') ?? '',
                    'Time' => $reservation ? ($reservation->start_time.' - '.$reservation->end_time) : '',
                    'Status' => $booking->status,
                    'Payment Status' => $booking->payment_status,
                    'Created At' => $booking->created_at?->format('Y-m-d H:i:s') ?? '',
                ];
            })
            ->toArray();

        $reportService->exportToCsv($bookings, 'bookings_'.now()->format('Y-m-d_His').'.csv');

        $this->redirect(route('filament.admin.pages.reports'));
    }
}
