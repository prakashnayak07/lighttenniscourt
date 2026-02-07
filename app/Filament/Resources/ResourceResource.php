<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Models\Organization;
use App\Models\Resource as CourtResource;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

class ResourceResource extends Resource
{
    protected static ?string $model = CourtResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Courts';

    protected static ?string $modelLabel = 'Court';

    protected static ?string $pluralModelLabel = 'Courts';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?int $navigationSort = 3;

    protected static array $surfaceTypeOptions = [
        'clay' => 'Clay',
        'hard' => 'Hard Court',
        'grass' => 'Grass',
        'carpet' => 'Carpet',
        'synthetic' => 'Synthetic',
    ];

    protected static array $statusOptions = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'maintenance' => 'Under Maintenance',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('Court details')
                    ->description('Name, surface, location and availability.')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->options(fn() => Organization::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->default(fn() => config('app.current_organization_id'))
                            ->required(fn() => auth()->user()?->isSuperAdmin())
                            ->visible(fn() => auth()->user()?->isSuperAdmin()),
                        Forms\Components\TextInput::make('name')
                            ->label('Court name')
                            ->placeholder('e.g. Court 1, Center Court')
                            ->required()
                            ->maxLength(128),
                        Forms\Components\Select::make('surface_type')
                            ->label('Surface type')
                            ->options(static::$surfaceTypeOptions)
                            ->required()
                            ->default('hard')
                            ->native(false),
                        Forms\Components\Toggle::make('is_indoor')
                            ->label('Indoor court')
                            ->default(false)
                            ->inline(false),
                        Forms\Components\Toggle::make('has_lighting')
                            ->label('Has lighting')
                            ->default(false)
                            ->inline(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(static::$statusOptions)
                            ->required()
                            ->default('enabled')
                            ->native(false),
                        Forms\Components\TextInput::make('priority')
                            ->label('Display order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Higher number appears first in lists.'),
                    ])
                    ->columns(2),
                SchemaSection::make('Schedule')
                    ->description('Opening hours and booking slot length.')
                    ->schema([
                        Forms\Components\TimePicker::make('daily_start_time')
                            ->prefix('Starts')
                            ->label('Opens at')
                            ->required()
                            ->default('07:00')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('daily_end_time')
                            ->prefix('Ends')
                            ->label('Closes at')
                            ->required()
                            ->default('22:00')
                            ->seconds(false),
                        Forms\Components\TextInput::make('time_block_minutes')
                            ->label('Slot duration (minutes)')
                            ->numeric()
                            ->default(60)
                            ->required()
                            ->minValue(15)
                            ->helperText('Length of each bookable slot.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Court')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),
                Tables\Columns\BadgeColumn::make('surface_type')
                    ->label('Surface')
                    ->formatStateUsing(fn(string $state) => static::$surfaceTypeOptions[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_indoor')
                    ->label('Indoor')
                    ->boolean()
                    ->trueIcon('heroicon-o-building-office')
                    ->falseIcon('heroicon-o-sun')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_lighting')
                    ->label('Lighting')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-bolt-slash')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'enabled',
                        'danger' => 'disabled',
                        'warning' => 'maintenance',
                    ]),
                Tables\Columns\TextColumn::make('daily_start_time')
                    ->label('Opens')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_end_time')
                    ->label('Closes')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_block_minutes')
                    ->label('Slot')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(static::$statusOptions),
                Tables\Filters\SelectFilter::make('surface_type')
                    ->label('Surface')
                    ->options(static::$surfaceTypeOptions),
                Tables\Filters\TernaryFilter::make('is_indoor')
                    ->label('Indoor'),
                Tables\Filters\TernaryFilter::make('has_lighting')
                    ->label('Has lighting'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                    Actions\Action::make('clone')
                        ->label('Clone')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (CourtResource $record) {
                            $clone = $record->replicate();
                            $clone->name = preg_match('/\s*\(Copy(?:\s*\d*)?\)\s*$/', $record->name)
                                ? $record->name
                                : $record->name.' (Copy)';
                            $clone->save();
                            Notification::make()
                                ->title('Court cloned')
                                ->body('"'.$clone->name.'" has been created. You can edit it now.')
                                ->success()
                                ->send();

                            return redirect(ResourceResource::getUrl('edit', ['record' => $clone]));
                        }),
                    Actions\DeleteAction::make(),
                ]),
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
