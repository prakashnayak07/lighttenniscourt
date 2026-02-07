<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('resource_id')
                    ->relationship('resource', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'no_show' => 'No Show',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                        'refund_pending' => 'Refund Pending',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'wallet' => 'Wallet',
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                    ])
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resource.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
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
                        'danger' => 'refunded',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
                Tables\Filters\SelectFilter::make('payment_status'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('date', '<=', $data['until']));
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
