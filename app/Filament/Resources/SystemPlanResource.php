<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemPlanResource\Pages;
use App\Models\SystemPlan;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SystemPlanResource extends Resource
{
    protected static ?string $model = SystemPlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Hidden::make('features_config')
                    ->default([]),
                SchemaSection::make('Plan details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(128),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('price_cents')
                            ->numeric()
                            ->required()
                            ->helperText('Price in cents (e.g., 10000 = $100.00)'),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'AUD' => 'AUD - Australian Dollar',
                                'CAD' => 'CAD - Canadian Dollar',
                                'CHF' => 'CHF - Swiss Franc',
                                'JPY' => 'JPY - Japanese Yen',
                            ])
                            ->default('USD')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('billing_interval')
                            ->options([
                                'month' => 'Monthly',
                                'year' => 'Yearly',
                            ])
                            ->default('month')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                SchemaSection::make('Feature limits')
                    ->schema([
                        Forms\Components\TextInput::make('features_config.max_courts')
                            ->label('Max courts/resources')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Leave empty for unlimited.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_interval')
                    ->formatStateUsing(fn (string $state) => $state === 'month' ? 'Monthly' : 'Yearly')
                    ->sortable(),
                Tables\Columns\TextColumn::make('features_config.max_courts')
                    ->label('Max courts')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited'),
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
            'index' => Pages\ListSystemPlans::route('/'),
            'create' => Pages\CreateSystemPlan::route('/create'),
            'edit' => Pages\EditSystemPlan::route('/{record}/edit'),
        ];
    }
}
