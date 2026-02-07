<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemPlan extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'currency',
        'billing_interval',
        'features_config',
        'stripe_product_id',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features_config' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
