<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'logo_url',
        'website',
        'description',
        'currency',
        'timezone',
        'billing_status',
        'stripe_customer_id',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        $subscription = $this->subscription;

        if (! $subscription || $subscription->status !== 'active') {
            return null;
        }

        return $subscription;
    }

    public function getMaxCourtsLimit(): ?int
    {
        $plan = $this->activeSubscription()?->plan;
        $maxCourts = $plan?->features_config['max_courts'] ?? null;

        if ($maxCourts === null) {
            return null;
        }

        return (int) $maxCourts;
    }

    public function canCreateAnotherResource(): bool
    {
        if (! $this->activeSubscription()) {
            return false;
        }

        $limit = $this->getMaxCourtsLimit();

        if ($limit === null) {
            return true;
        }

        return $this->resources()->count() < $limit;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', \App\Enums\UserRole::Customer->value);
    }

    public function membershipTypes(): HasMany
    {
        return $this->hasMany(ClubMembershipType::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(UserWallet::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
