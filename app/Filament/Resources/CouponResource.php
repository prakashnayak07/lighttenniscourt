<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->helperText('Unique coupon code'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('discount_type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ])
                    ->required()
                    ->default('percentage'),
                Forms\Components\TextInput::make('discount_value')
                    ->numeric()
                    ->required()
                    ->helperText('Percentage (0-100) or fixed amount in cents'),
                Forms\Components\DatePicker::make('valid_until')
                    ->nullable()
                    ->helperText('Leave empty for no expiration'),
                Forms\Components\TextInput::make('max_uses')
                    ->numeric()
                    ->nullable()
                    ->helperText('Leave empty for unlimited uses'),
                Forms\Components\TextInput::make('usage_count')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('discount_type')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('discount_value')
                    ->formatStateUsing(function ($record) {
                        if ($record->discount_type === 'percentage') {
                            return $record->discount_value.'%';
                        }

                        return '$'.number_format($record->discount_value / 100, 2);
                    }),
                Tables\Columns\TextColumn::make('usage_count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_uses')
                    ->default('Unlimited'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->default('No expiration'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->where('valid_until', '<', now())),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
