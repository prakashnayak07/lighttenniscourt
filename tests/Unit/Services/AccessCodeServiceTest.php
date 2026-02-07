<?php

use App\Models\Booking;
use App\Services\AccessCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->accessCodeService = app(AccessCodeService::class);
    $this->booking = Booking::factory()->create();
});

test('generates unique access code', function () {
    $code1 = $this->accessCodeService->generateAccessCode($this->booking);
    $code2 = $this->accessCodeService->generateAccessCode($this->booking);

    expect($code1)->not->toBe($code2)
        ->and($code1)->toMatch('/^[A-Z0-9]+-[A-Z0-9]+-\d{8}-[A-Z0-9]{4}$/');
});

test('generates QR code SVG', function () {
    $code = 'TEST-C1-20260207-ABCD';
    $qrCode = $this->accessCodeService->generateQRCode($code);

    expect($qrCode)->toContain('<svg')
        ->and($qrCode)->toContain('</svg>');
});

test('validates correct access code', function () {
    $code = $this->accessCodeService->generateAccessCode($this->booking);
    $this->booking->update(['access_code' => $code, 'status' => 'confirmed']);

    $validatedBooking = $this->accessCodeService->validateAccessCode($code);

    expect($validatedBooking)->not->toBeNull()
        ->and($validatedBooking->id)->toBe($this->booking->id);
});

test('returns null for invalid access code', function () {
    $validatedBooking = $this->accessCodeService->validateAccessCode('INVALID-CODE');

    expect($validatedBooking)->toBeNull();
});

test('validates time window correctly', function () {
    $this->booking->update([
        'date' => now()->format('Y-m-d'),
        'start_time' => now()->addMinutes(15)->format('H:i:s'),
    ]);

    $isValid = $this->accessCodeService->isWithinValidTimeWindow($this->booking);

    expect($isValid)->toBeTrue();
});

test('rejects code outside time window', function () {
    $this->booking->update([
        'date' => now()->format('Y-m-d'),
        'start_time' => now()->addHours(2)->format('H:i:s'),
    ]);

    $isValid = $this->accessCodeService->isWithinValidTimeWindow($this->booking);

    expect($isValid)->toBeFalse();
});

test('marks access code as used', function () {
    $code = $this->accessCodeService->generateAccessCode($this->booking);
    $this->booking->update(['access_code' => $code]);

    expect($this->booking->access_code_used_at)->toBeNull();

    $this->accessCodeService->markAsUsed($this->booking);
    $this->booking->refresh();

    expect($this->booking->access_code_used_at)->not->toBeNull();
});
