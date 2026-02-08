<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Models\ClubMembershipType;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ClubMembershipTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'membershipTypes';

    protected static ?string $title = 'Club Membership Types';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (auth()->user()?->isSuperAdmin()) {
            return true;
        }

        return auth()->user()?->isAdmin() && auth()->user()?->organization_id === $ownerRecord?->id;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('Details')
                    ->schema([
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
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['organization_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
