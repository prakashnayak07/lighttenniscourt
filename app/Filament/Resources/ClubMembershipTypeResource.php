<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubMembershipTypeResource\Pages;
use App\Models\ClubMembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClubMembershipTypeResource extends Resource
{
    protected static ?string $model = ClubMembershipType::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Membership Types';

    protected static $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price_cents')
                    ->numeric()
                    ->required()
                    ->helperText('Price in cents (e.g., 10000 = $100.00)'),
                Forms\Components\TextInput::make('duration_months')
                    ->numeric()
                    ->required()
                    ->default(12)
                    ->helperText('Duration in months'),
                Forms\Components\TextInput::make('discount_percentage')
                    ->numeric()
                    ->default(0)
                    ->helperText('Discount percentage for bookings (0-100)'),
                Forms\Components\KeyValue::make('benefits')
                    ->label('Benefits (JSON)')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
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
                Tables\Columns\TextColumn::make('duration_months')
                    ->suffix(' months')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->suffix('%')
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
            'index' => Pages\ListClubMembershipTypes::route('/'),
            'create' => Pages\CreateClubMembershipType::route('/create'),
            'edit' => Pages\EditClubMembershipType::route('/{record}/edit'),
        ];
    }
}
