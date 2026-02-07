<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
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
                    ->options([
                        UserRole::SuperAdmin->value => 'Super Admin',
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
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
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
                Tables\Columns\BadgeColumn::make('role')
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => UserStatus::Active->value,
                        'warning' => UserStatus::Pending->value,
                        'danger' => UserStatus::Banned->value,
                    ])
                    ->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('organization.name')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        UserRole::SuperAdmin->value => 'Super Admin',
                        UserRole::Admin->value => 'Admin',
                        UserRole::Staff->value => 'Staff',
                        UserRole::Coach->value => 'Coach',
                        UserRole::Customer->value => 'Customer',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Disabled->value => 'Disabled',
                        UserStatus::Banned->value => 'Banned',
                        UserStatus::Pending->value => 'Pending',
                    ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
