<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Reservation;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['reservation_date'], $data['start_time'], $data['end_time']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        if (! empty($data['reservation_date']) && ! empty($data['start_time']) && ! empty($data['end_time'])) {
            Reservation::create([
                'booking_id' => $this->record->getKey(),
                'resource_id' => $this->record->resource_id,
                'reservation_date' => $data['reservation_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);
        }
    }
}
