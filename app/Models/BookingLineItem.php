<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingLineItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'description',
        'quantity',
        'unit_price_cents',
        'total_cents',
        'type',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
