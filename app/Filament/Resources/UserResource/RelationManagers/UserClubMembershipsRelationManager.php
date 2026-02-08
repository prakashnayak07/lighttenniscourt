<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\ClubMembershipType;
use App\Models\Organization;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

class UserClubMembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $title = 'Club Memberships';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (auth()->user()?->isSuperAdmin()) {
            return true;
        }

        return auth()->user()?->isAdmin()
            && auth()->user()?->organization_id === $ownerRecord?->organization_id;
    }

    public function form(Schema $schema): Schema
    {
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        return $schema
            ->schema([
                SchemaSection::make('Membership')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->options(fn () => Organization::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->required($isSuperAdmin)
                            ->dehydrated(false)
                            ->visible($isSuperAdmin)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('membership_type_id', null);
                            }),
                        Forms\Components\Select::make('membership_type_id')
                            ->label('Membership type')
                            ->options(fn (Get $get) => ClubMembershipType::query()
                                ->when($isSuperAdmin, function ($query) use ($get) {
                                    $organizationId = $get('organization_id');
                                    if ($organizationId) {
                                        $query->where('organization_id', $organizationId);
                                    }
                                })
                                ->when(! $isSuperAdmin, fn ($query) => $query->where('organization_id', $this->getOwnerRecord()->organization_id))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Valid from')
                            ->required(),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Valid until')
                            ->after('valid_from')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('membershipType.name')
                    ->label('Membership type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
