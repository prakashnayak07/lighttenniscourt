<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use App\Models\SystemPlan;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    public static function canView($record): bool
    {
        if (auth()->user()?->isSuperAdmin()) {
            return true;
        }

        return auth()->user()?->isAdmin() && auth()->user()?->organization_id === $record?->id;
    }

    public static function canEdit($record): bool
    {
        return static::canView($record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->whereKey(auth()->user()?->organization_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('Profile')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Profile logo')
                            ->image()
                            ->disk('public')
                            ->directory('organizations/logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->nullable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('subdomain')
                            ->label('Slug / Subdomain')
                            ->unique(ignoreRecord: true)
                            ->maxLength(64)
                            ->placeholder('clubname.yoursaas.com')
                            ->nullable(),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                                'AUD' => 'AUD - Australian Dollar',
                                'CAD' => 'CAD - Canadian Dollar',
                                'CHF' => 'CHF - Swiss Franc',
                                'JPY' => 'JPY - Japanese Yen',
                            ])
                            ->default('USD')
                            ->searchable(),
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->options(collect(timezone_identifiers_list())->mapWithKeys(fn($tz) => [$tz => $tz]))
                            ->searchable()
                            ->default('UTC'),
                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->options([
                                1 => 'Active',
                                0 => 'Inactive',
                            ])
                            ->required()
                            ->default(1)
                            ->native(false),
                    ])
                    ->columns(1),
                SchemaSection::make('Subscription plans')
                    ->description('All system plans are buyable. Active plan is highlighted.')
                    ->schema([
                        Forms\Components\Placeholder::make('system_plan_overview')
                            ->label('Plans')
                            ->content(function (?Organization $record) {
                                if (! $record) {
                                    return 'Save the organization to view plans.';
                                }

                                $activePlanId = $record->activeSubscription()?->plan_id;
                                $plans = SystemPlan::query()
                                    ->where('is_active', true)
                                    ->orderBy('price_cents')
                                    ->get();

                                if ($plans->isEmpty()) {
                                    return 'No active system plans available.';
                                }

                                $lines = $plans->map(function (SystemPlan $plan) use ($activePlanId) {
                                    $price = '$'.number_format($plan->price_cents / 100, 2);
                                    $interval = $plan->billing_interval === 'year' ? 'year' : 'month';
                                    $maxCourts = $plan->features_config['max_courts'] ?? null;
                                    $maxCourtsLabel = $maxCourts === null ? 'Unlimited courts' : "{$maxCourts} courts";
                                    $statusLabel = $plan->id === $activePlanId ? 'Active' : 'Buyable';

                                    return "<li><strong>{$plan->name}</strong> — {$price}/{$interval} · {$maxCourtsLabel} · {$statusLabel}</li>";
                                })->implode('');

                                return new HtmlString("<ul class=\"list-disc pl-5 space-y-1\">{$lines}</ul>");
                            }),
                    ])
                    ->visible(fn (?Organization $record) => $record !== null),
                SchemaSection::make('Description')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('settings')
                            ->label('Settings (JSON)')
                            ->helperText('Optional key-value pairs for club-specific config (e.g. opening hours, court rules, custom labels). Stored as JSON; no database changes needed for new keys.')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->getStateUsing(fn ($record) => $record->logo_url ? asset('storage/'.$record->logo_url) : null)
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? 'Org')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->label('Website')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Timezone')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('customers_count')
                    ->counts('customers')
                    ->label('Customers'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
