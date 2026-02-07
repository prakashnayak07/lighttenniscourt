<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Models\Organization;
use App\Models\Resource as CourtResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ResourceResource extends Resource
{
    protected static ?string $model = CourtResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Courts';

    protected static ?string $modelLabel = 'Court';

    protected static ?string $pluralModelLabel = 'Courts';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'indoor' => 'Indoor',
                        'outdoor' => 'Outdoor',
                        'clay' => 'Clay',
                        'hard' => 'Hard Court',
                        'grass' => 'Grass',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'enabled' => 'Enabled',
                        'disabled' => 'Disabled',
                        'maintenance' => 'Under Maintenance',
                    ])
                    ->required()
                    ->default('enabled'),
                Forms\Components\TextInput::make('capacity')
                    ->numeric()
                    ->default(4)
                    ->helperText('Maximum number of players'),
                Forms\Components\TimePicker::make('daily_start_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('daily_end_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TextInput::make('time_block_minutes')
                    ->numeric()
                    ->default(60)
                    ->required()
                    ->helperText('Duration of each booking slot in minutes'),
                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0)
                    ->helperText('Display order (higher = first)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'enabled',
                        'danger' => 'disabled',
                        'warning' => 'maintenance',
                    ]),
                Tables\Columns\TextColumn::make('daily_start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('daily_end_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('time_block_minutes')
                    ->suffix(' min'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enabled' => 'Enabled',
                        'disabled' => 'Disabled',
                        'maintenance' => 'Under Maintenance',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'indoor' => 'Indoor',
                        'outdoor' => 'Outdoor',
                        'clay' => 'Clay',
                        'hard' => 'Hard Court',
                        'grass' => 'Grass',
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
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
}
