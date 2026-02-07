<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'name',
        'surface_type',
        'is_indoor',
        'has_lighting',
        'status',
        'priority',
        'daily_start_time',
        'daily_end_time',
        'time_block_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_indoor' => 'boolean',
            'has_lighting' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
