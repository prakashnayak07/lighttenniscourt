<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'resource_id',
        'status',
        'payment_status',
        'visibility',
        'notes',
        'check_in_at',
    ];

    public function firstReservation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Reservation::class)->oldestOfMany();
    }

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(BookingLineItem::class);
    }

    public function accessCodes(): HasMany
    {
        return $this->hasMany(BookingAccessCode::class);
    }
}
