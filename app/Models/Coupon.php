<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'code',
        'discount_type',
        'discount_value',
        'valid_until',
        'usage_limit',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
