<?php

use App\Models\Resource;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resource = Resource::factory()->create([
        'status' => 'enabled',
        'daily_start_time' => '08:00:00',
        'daily_end_time' => '22:00:00',
        'time_block_minutes' => 60,
    ]);
    $this->availabilityService = app(AvailabilityService::class);
});

test('generates correct time slots for a resource', function () {
    $date = Carbon::tomorrow();
    $slots = $this->availabilityService->getAvailableSlots($this->resource, $date);

    expect($slots)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($slots->count())->toBeGreaterThan(0)
        ->and($slots->first())->toHaveKeys(['start_time', 'end_time', 'date']);
});

test('resource is available for valid time range', function () {
    $date = Carbon::tomorrow();
    $isAvailable = $this->availabilityService->isAvailable(
        $this->resource,
        $date,
        '14:00:00',
        '15:00:00'
    );

    expect($isAvailable)->toBeTrue();
});

test('resource is not available outside operating hours', function () {
    $date = Carbon::tomorrow();
    $isAvailable = $this->availabilityService->isAvailable(
        $this->resource,
        $date,
        '23:00:00',
        '24:00:00'
    );

    expect($isAvailable)->toBeFalse();
});

test('resource is not available when disabled', function () {
    $this->resource->update(['status' => 'disabled']);
    $date = Carbon::tomorrow();
    
    $isAvailable = $this->availabilityService->isAvailable(
        $this->resource,
        $date,
        '14:00:00',
        '15:00:00'
    );

    expect($isAvailable)->toBeFalse();
});

test('excludes booked slots from available slots', function () {
    $date = Carbon::tomorrow();
    
    // Create a booking
    $booking = \App\Models\Booking::factory()->create(['status' => 'confirmed']);
    $booking->reservations()->create([
        'resource_id' => $this->resource->id,
        'reservation_date' => $date->format('Y-m-d'),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
    ]);

    $slots = $this->availabilityService->getAvailableSlots($this->resource, $date);

    // Check that 14:00-15:00 slot is not in available slots
    $hasConflict = $slots->contains(function ($slot) {
        return $slot['start_time'] === '14:00:00' && $slot['end_time'] === '15:00:00';
    });

    expect($hasConflict)->toBeFalse();
});

test('excludes maintenance slots from available slots', function () {
    $date = Carbon::tomorrow();
    
    // Create maintenance schedule
    \App\Models\MaintenanceSchedule::factory()->create([
        'resource_id' => $this->resource->id,
        'start_datetime' => $date->setTime(14, 0),
        'end_datetime' => $date->setTime(15, 0),
        'status' => 'scheduled',
    ]);

    $slots = $this->availabilityService->getAvailableSlots($this->resource, $date);

    // Check that 14:00-15:00 slot is not in available slots
    $hasConflict = $slots->contains(function ($slot) {
        return $slot['start_time'] === '14:00:00' && $slot['end_time'] === '15:00:00';
    });

    expect($hasConflict)->toBeFalse();
});
