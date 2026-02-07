<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'access_code' => $this->access_code,
            'visibility' => $this->visibility,
            'notes' => $this->notes,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            'resource' => new ResourceResource($this->whenLoaded('resource')),
            'user' => new UserResource($this->whenLoaded('user')),
            
            'reservations' => $this->whenLoaded('reservations', function () {
                return $this->reservations->map(fn ($reservation) => [
                    'id' => $reservation->id,
                    'date' => $reservation->reservation_date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                ]);
            }),
            
            'line_items' => $this->whenLoaded('lineItems', function () {
                return $this->lineItems->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_cents' => $item->total_cents,
                ]);
            }),
            
            'total_amount_cents' => $this->lineItems?->sum('total_cents'),
            'total_amount' => '$' . number_format(($this->lineItems?->sum('total_cents') ?? 0) / 100, 2),
        ];
    }
}
