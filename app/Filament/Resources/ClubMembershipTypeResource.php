<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubMembershipTypeResource\Pages;
use App\Models\ClubMembershipType;
use App\Models\Organization;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClubMembershipTypeResource extends Resource
{
    protected static ?string $model = ClubMembershipType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Membership Types';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    public static function canEdit($record): bool
    {
        if (auth()->user()?->isSuperAdmin()) {
            return true;
        }

        return auth()->user()?->isAdmin()
            && auth()->user()?->organization_id === $record?->organization_id;
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('organization_id', auth()->user()?->organization_id);
    }

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
                Forms\Components\Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id)
                    ->visible(fn () => ! auth()->user()?->isSuperAdmin()),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('max_resources')
                    ->label('Max courts/resources')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Leave empty for unlimited.'),
                Forms\Components\TextInput::make('price_cents')
                    ->numeric()
                    ->required()
                    ->helperText('Price in cents (e.g., 10000 = $100.00)'),
                Forms\Components\Select::make('billing_cycle')
                    ->options([
                        'one_time' => 'One time',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->default('yearly')
                    ->required(),
                Forms\Components\TextInput::make('booking_window_days')
                    ->label('Booking window (days)')
                    ->numeric()
                    ->minValue(1)
                    ->default(7)
                    ->helperText('How many days in advance members can book.'),
                Forms\Components\TextInput::make('max_active_bookings')
                    ->label('Max active bookings')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Leave empty for no limit.'),
                Forms\Components\TextInput::make('court_fee_discount_percent')
                    ->label('Court fee discount (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->step(0.01),
                Forms\Components\Toggle::make('is_public')
                    ->label('Public')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'one_time' => 'One time',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_resources')
                    ->label('Max courts')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited'),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public'),
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
            'index' => Pages\ListClubMembershipTypes::route('/'),
            'create' => Pages\CreateClubMembershipType::route('/create'),
            'edit' => Pages\EditClubMembershipType::route('/{record}/edit'),
        ];
    }
}
