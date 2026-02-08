<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Models\SystemPlan;
use Filament\Pages\Page;

class OrganizationPlans extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Subscription Plans';

    protected string $view = 'filament.pages.organization-plans';

    public ?Organization $organization = null;

    public ?int $activePlanId = null;

    public $plans;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $organizationId = config('app.current_organization_id') ?? auth()->user()?->organization_id;
        $this->organization = $organizationId
            ? Organization::query()->with('subscription.plan')->find($organizationId)
            : null;

        $this->activePlanId = $this->organization?->activeSubscription()?->plan_id;
        $this->plans = SystemPlan::query()
            ->where('is_active', true)
            ->orderBy('price_cents')
            ->get();
    }
}
