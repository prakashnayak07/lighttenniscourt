<?php

use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use App\Services\AccessCodeService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->resource = Resource::factory()->create([
        'status' => 'enabled',
        'hourly_rate_cents' => 5000,
    ]);
    $this->bookingService = app(BookingService::class);
});

test('can create a booking successfully', function () {
    $data = [
        'user_id' => $this->user->id,
        'resource_id' => $this->resource->id,
        'date' => now()->addDays(1)->format('Y-m-d'),
        'start_time' => '14:00',
        'end_time' => '15:00',
    ];

    $booking = $this->bookingService->createBooking($data);

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->status)->toBe('pending')
        ->and($booking->payment_status)->toBe('pending')
        ->and($booking->user_id)->toBe($this->user->id)
        ->and($booking->reservations)->toHaveCount(1);
});

test('generates access code on booking confirmation', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);

    expect($booking->access_code)->toBeNull();

    $booking->update([
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $accessCodeService = app(AccessCodeService::class);
    $code = $accessCodeService->generateAccessCode($booking);
    $booking->update(['access_code' => $code]);

    $booking->refresh();

    expect($booking->access_code)->not->toBeNull()
        ->and($booking->access_code)->toMatch('/^[A-Z0-9]+-[A-Z0-9]+-\d{8}-[A-Z0-9]{4}$/');
});

test('cannot create booking with conflicting time', function () {
    // Create first booking
    Booking::factory()->create([
        'status' => 'confirmed',
    ])->reservations()->create([
        'resource_id' => $this->resource->id,
        'reservation_date' => now()->addDays(1)->format('Y-m-d'),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
    ]);

    // Try to create conflicting booking
    $data = [
        'user_id' => $this->user->id,
        'resource_id' => $this->resource->id,
        'date' => now()->addDays(1)->format('Y-m-d'),
        'start_time' => '14:30',
        'end_time' => '15:30',
    ];

    expect(fn() => $this->bookingService->createBooking($data))
        ->toThrow(\Exception::class);
});

test('can cancel booking and update status', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $this->bookingService->cancelBooking($booking);

    $booking->refresh();

    expect($booking->status)->toBe('cancelled');
});

test('creates line items for booking', function () {
    $data = [
        'user_id' => $this->user->id,
        'resource_id' => $this->resource->id,
        'date' => now()->addDays(1)->format('Y-m-d'),
        'start_time' => '14:00',
        'end_time' => '15:00',
    ];

    $booking = $this->bookingService->createBooking($data);

    expect($booking->lineItems)->toHaveCount(1)
        ->and($booking->lineItems->first()->unit_price_cents)->toBe(5000);
});
