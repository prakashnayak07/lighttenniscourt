<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    /** @var array{reservationDate: mixed, startTime: mixed, endTime: mixed}|null */
    protected ?array $reservationDataToSync = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $reservation = $this->record->reservations()->orderBy('id')->first();
        if ($reservation) {
            $data['reservation_date'] = $reservation->reservation_date?->format('Y-m-d');
            $data['start_time'] = $reservation->start_time;
            $data['end_time'] = $reservation->end_time;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $reservationDate = $data['reservation_date'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        unset($data['reservation_date'], $data['start_time'], $data['end_time']);

        $this->reservationDataToSync = compact('reservationDate', 'startTime', 'endTime');

        return $data;
    }

    protected function afterSave(): void
    {
        $sync = $this->reservationDataToSync ?? null;
        if (! $sync || ! $sync['reservationDate'] || ! $sync['startTime'] || ! $sync['endTime']) {
            return;
        }
        $reservation = $this->record->reservations()->orderBy('id')->first();
        if ($reservation) {
            $reservation->update([
                'reservation_date' => $sync['reservationDate'],
                'start_time' => $sync['startTime'],
                'end_time' => $sync['endTime'],
            ]);
        } else {
            $this->record->reservations()->create([
                'resource_id' => $this->record->resource_id,
                'reservation_date' => $sync['reservationDate'],
                'start_time' => $sync['startTime'],
                'end_time' => $sync['endTime'],
            ]);
        }
    }
}
