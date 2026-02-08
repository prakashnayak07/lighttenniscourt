<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Users';

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
                SchemaSection::make('User')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->options(fn () => auth()->user()?->isSuperAdmin()
                                ? [
                                    UserRole::SuperAdmin->value => 'Super Admin',
                                    UserRole::Admin->value => 'Admin',
                                    UserRole::Staff->value => 'Staff',
                                    UserRole::Coach->value => 'Coach',
                                    UserRole::Customer->value => 'Customer',
                                ]
                                : [
                                    UserRole::Admin->value => 'Admin',
                                    UserRole::Staff->value => 'Staff',
                                    UserRole::Coach->value => 'Coach',
                                    UserRole::Customer->value => 'Customer',
                                ])
                            ->required()
                            ->default(UserRole::Customer->value),
                        Forms\Components\Select::make('status')
                            ->options([
                                UserStatus::Active->value => 'Active',
                                UserStatus::Disabled->value => 'Disabled',
                                UserStatus::Banned->value => 'Banned',
                                UserStatus::Pending->value => 'Pending',
                            ])
                            ->required()
                            ->default(UserStatus::Active->value),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        UserStatus::Active->value => 'success',
                        UserStatus::Pending->value => 'warning',
                        UserStatus::Banned->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state->label()),
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
