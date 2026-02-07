<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use BelongsToOrganization;

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
}
