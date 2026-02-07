<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'hourly_rate_cents' => $this->hourly_rate_cents,
            'hourly_rate' => '$' . number_format($this->hourly_rate_cents / 100, 2),
            'time_block_minutes' => $this->time_block_minutes,
            'daily_start_time' => $this->daily_start_time,
            'daily_end_time' => $this->daily_end_time,
            'image_url' => $this->image_url,
            'amenities' => $this->amenities,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
