<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserClubMembershipResource\Pages;
use App\Models\ClubMembershipType;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserClubMembership;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserClubMembershipResource extends Resource
{
    protected static ?string $model = UserClubMembership::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 4;

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
            && $record?->user?->organization_id === auth()->user()?->organization_id;
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'membershipType']);

        if (auth()->user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('user', function ($builder) {
            $builder->where('organization_id', auth()->user()?->organization_id);
        });
    }

    public static function form(Schema $schema): Schema
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
                            ->visible($isSuperAdmin),
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(fn (Get $get) => User::query()
                                ->when($isSuperAdmin, function ($query) use ($get) {
                                    $organizationId = $get('organization_id');
                                    if ($organizationId) {
                                        $query->where('organization_id', $organizationId);
                                    }
                                })
                                ->when(! $isSuperAdmin, fn ($query) => $query->where('organization_id', auth()->user()?->organization_id))
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (User $user) => [
                                    $user->id => trim($user->first_name.' '.$user->last_name) ?: $user->email,
                                ]))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('membership_type_id')
                            ->label('Membership type')
                            ->options(fn (Get $get) => ClubMembershipType::query()
                                ->when($isSuperAdmin, function ($query) use ($get) {
                                    $organizationId = $get('organization_id');
                                    if ($organizationId) {
                                        $query->where('organization_id', $organizationId);
                                    }
                                })
                                ->when(! $isSuperAdmin, fn ($query) => $query->where('organization_id', auth()->user()?->organization_id))
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user')
                    ->label('User')
                    ->formatStateUsing(function ($state, UserClubMembership $record) {
                        $user = $record->user;
                        if (! $user) {
                            return null;
                        }

                        return trim($user->first_name.' '.$user->last_name) ?: $user->email;
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('user', function ($builder) use ($search) {
                            $builder
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }),
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
                Tables\Columns\TextColumn::make('user.organization.name')
                    ->label('Organization')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
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
            'index' => Pages\ListUserClubMemberships::route('/'),
            'create' => Pages\CreateUserClubMembership::route('/create'),
            'edit' => Pages\EditUserClubMembership::route('/{record}/edit'),
        ];
    }
}
