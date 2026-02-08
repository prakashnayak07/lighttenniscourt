<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\MaintenanceScheduleResource\Pages;
use App\Models\MaintenanceSchedule;
use App\Models\Organization;
use App\Models\Resource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceScheduleResource extends FilamentResource
{
    protected static ?string $model = MaintenanceSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 5;

    protected static array $typeOptions = [
        'routine' => 'Routine Maintenance',
        'repair' => 'Repair',
        'cleaning' => 'Cleaning',
        'upgrade' => 'Upgrade',
    ];

    protected static array $statusOptions = [
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('Schedule')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->options(fn () => Organization::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->default(fn () => config('app.current_organization_id'))
                            ->required(fn () => auth()->user()?->isSuperAdmin())
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\Select::make('resource_id')
                            ->label('Court')
                            ->relationship(
                                'resource',
                                'name',
                                fn ($query, $get) => $get('organization_id')
                                    ? $query->where('organization_id', $get('organization_id'))
                                    : $query
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('start_datetime')
                            ->label('Start')
                            ->required()
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('end_datetime')
                            ->label('End')
                            ->required()
                            ->seconds(false)
                            ->after('start_datetime'),
                        Forms\Components\Select::make('type')
                            ->options(static::$typeOptions)
                            ->required()
                            ->default('routine')
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(static::$statusOptions)
                            ->required()
                            ->default('scheduled')
                            ->native(false),
                    ])
                    ->columns(2),
                SchemaSection::make('Assignment & notes')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->options(fn ($get) => User::query()
                                ->when($get('organization_id'), fn ($q) => $q->where('organization_id', $get('organization_id')))
                                ->where('role', UserRole::Staff->value)
                                ->get()
                                ->mapWithKeys(fn (User $u) => [$u->id => trim($u->first_name.' '.$u->last_name) ?: $u->email]))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->seconds(false)
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Court')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Start')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('End')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'routine',
                        'warning' => 'repair',
                        'success' => 'cleaning',
                        'info' => 'upgrade',
                    ]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'scheduled',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('assignedUser')
                    ->label('Assigned To')
                    ->formatStateUsing(fn (?User $user) => $user ? trim($user->first_name.' '.$user->last_name) ?: $user->email : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(static::$typeOptions),
                Tables\Filters\SelectFilter::make('status')
                    ->options(static::$statusOptions),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_datetime', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceSchedules::route('/'),
            'create' => Pages\CreateMaintenanceSchedule::route('/create'),
            'edit' => Pages\EditMaintenanceSchedule::route('/{record}/edit'),
        ];
    }
}
