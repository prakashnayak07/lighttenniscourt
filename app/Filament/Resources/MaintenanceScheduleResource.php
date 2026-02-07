<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceScheduleResource\Pages;
use App\Models\MaintenanceSchedule;
use App\Models\Resource;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource as FilamentResource;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceScheduleResource extends FilamentResource
{
    protected static ?string $model = MaintenanceSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('resource_id')
                    ->label('Court')
                    ->options(Resource::pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\DateTimePicker::make('start_datetime')
                    ->label('Start Date & Time')
                    ->required()
                    ->seconds(false),

                Forms\Components\DateTimePicker::make('end_datetime')
                    ->label('End Date & Time')
                    ->required()
                    ->seconds(false)
                    ->after('start_datetime'),

                Forms\Components\Select::make('type')
                    ->options([
                        'routine' => 'Routine Maintenance',
                        'repair' => 'Repair',
                        'cleaning' => 'Cleaning',
                        'upgrade' => 'Upgrade',
                    ])
                    ->required()
                    ->default('routine'),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('scheduled'),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::where('role', 'staff')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Completed At')
                    ->seconds(false)
                    ->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'routine' => 'Routine',
                        'repair' => 'Repair',
                        'cleaning' => 'Cleaning',
                        'upgrade' => 'Upgrade',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
