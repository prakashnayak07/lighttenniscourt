<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMembershipType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'name',
        'price_cents',
        'billing_cycle',
        'booking_window_days',
        'max_active_bookings',
        'court_fee_discount_percent',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
