<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Organization;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms;
use Filament\Tables;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 4;

    protected static array $statusOptions = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        'no_show' => 'No Show',
    ];

    protected static array $paymentStatusOptions = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'refunded' => 'Refunded',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('Booking')
                    ->description('Who, which court, and when.')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->options(fn() => Organization::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->default(fn() => config('app.current_organization_id'))
                            ->required(fn() => auth()->user()?->isSuperAdmin())
                            ->visible(fn() => auth()->user()?->isSuperAdmin()),
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship(
                                'user',
                                'email',
                                fn($query, $get) => $get('organization_id')
                                    ? $query->where('organization_id', $get('organization_id'))
                                    : $query
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('resource_id')
                            ->label('Court')
                            ->relationship(
                                'resource',
                                'name',
                                fn($query, $get) => $get('organization_id')
                                    ? $query->where('organization_id', $get('organization_id'))
                                    : $query
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('reservation_date')
                            ->label('Date')
                            ->required()
                            ->native(false),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start time')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('End time')
                            ->required()
                            ->seconds(false),
                    ])
                    ->columns(2),
                SchemaSection::make('Status & payment')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(static::$statusOptions)
                            ->required()
                            ->default('pending')
                            ->native(false),
                        Forms\Components\Select::make('payment_status')
                            ->options(static::$paymentStatusOptions)
                            ->required()
                            ->default('pending')
                            ->native(false),
                        Forms\Components\Select::make('visibility')
                            ->options(['public' => 'Public', 'private' => 'Private'])
                            ->default('private')
                            ->native(false),
                    ])
                    ->columns(2),
                SchemaSection::make('Details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('check_in_at')
                            ->label('Check-in at')
                            ->nullable()
                            ->seconds(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Court')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('firstReservation.reservation_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('firstReservation.start_time')
                    ->label('Start')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('firstReservation.end_time')
                    ->label('End')
                    ->time('H:i'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                        'primary' => 'completed',
                        'secondary' => 'no_show',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'gray' => 'partial',
                        'danger' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(static::$statusOptions),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(static::$paymentStatusOptions),
                Tables\Filters\Filter::make('reservation_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereHas('reservations', fn($q2) => $q2->whereDate('reservation_date', '>=', $data['from'])))
                            ->when($data['until'], fn($q) => $q->whereHas('reservations', fn($q2) => $q2->whereDate('reservation_date', '<=', $data['until'])));
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn(Booking $record) => $record->update(['status' => 'confirmed']))
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
                Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn(Booking $record) => app(\App\Services\BookingService::class)->cancelBooking($record))
                    ->visible(fn(Booking $record) => in_array($record->status, ['pending', 'confirmed'])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
