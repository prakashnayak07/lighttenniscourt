<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricingRuleResource\Pages;
use App\Models\PricingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('resource_id')
                    ->relationship('resource', 'name')
                    ->nullable()
                    ->helperText('Leave empty for organization-wide pricing'),
                Forms\Components\Select::make('day_of_week')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->nullable()
                    ->helperText('Leave empty for all days'),
                Forms\Components\TimePicker::make('start_time')
                    ->nullable()
                    ->seconds(false)
                    ->helperText('Leave empty for all day'),
                Forms\Components\TimePicker::make('end_time')
                    ->nullable()
                    ->seconds(false),
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
                Tables\Columns\TextColumn::make('day_of_week')
                    ->default('All Days')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'All Days'),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i')
                    ->default('All Day'),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i'),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
