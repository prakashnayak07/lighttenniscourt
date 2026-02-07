<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricingRuleResource\Pages;
use App\Models\Organization;
use App\Models\PricingRule;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->options(fn () => Organization::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->default(fn () => config('app.current_organization_id'))
                    ->required(fn () => auth()->user()?->isSuperAdmin())
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Forms\Components\Select::make('resource_id')
                    ->relationship('resource', 'name')
                    ->nullable()
                    ->helperText('Leave empty for organization-wide pricing'),
                Forms\Components\Select::make('day_of_week_start')
                    ->label('Day of week (start)')
                    ->options([
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ])
                    ->default(1)
                    ->required()
                    ->helperText('Use Monday–Sunday for all days'),
                Forms\Components\Select::make('day_of_week_end')
                    ->label('Day of week (end)')
                    ->options([
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ])
                    ->default(7)
                    ->required(),
                Forms\Components\TimePicker::make('time_start')
                    ->label('Start time')
                    ->seconds(false)
                    ->default('00:00')
                    ->helperText('Use 00:00 for all day'),
                Forms\Components\TimePicker::make('time_end')
                    ->label('End time')
                    ->seconds(false)
                    ->default('23:59')
                    ->helperText('Use 23:59 for all day'),
                Forms\Components\TextInput::make('price_cents')
                    ->numeric()
                    ->required()
                    ->helperText('Price in cents (e.g., 5000 = $50.00)'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource.name')
                    ->default('All Courts')
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week_start')
                    ->label('Day(s)')
                    ->formatStateUsing(function ($state, $record) {
                        $labels = [
                            1 => 'Mon',
                            2 => 'Tue',
                            3 => 'Wed',
                            4 => 'Thu',
                            5 => 'Fri',
                            6 => 'Sat',
                            7 => 'Sun',
                        ];

                        $start = (int) $record->day_of_week_start;
                        $end = (int) $record->day_of_week_end;

                        if ($start === 1 && $end === 7) {
                            return 'All Days';
                        }

                        if ($start === $end) {
                            return $labels[$start] ?? '—';
                        }

                        return ($labels[$start] ?? '—').' - '.($labels[$end] ?? '—');
                    }),
                Tables\Columns\TextColumn::make('time_start')
                    ->label('Start time')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->time_start === '00:00:00' && $record->time_end === '23:59:59') {
                            return 'All Day';
                        }

                        return $state ? Carbon::parse($state)->format('H:i') : '—';
                    }),
                Tables\Columns\TextColumn::make('time_end')
                    ->label('End time')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->time_start === '00:00:00' && $record->time_end === '23:59:59') {
                            return 'All Day';
                        }

                        return $state ? Carbon::parse($state)->format('H:i') : '—';
                    }),
                Tables\Columns\TextColumn::make('price_cents')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}
