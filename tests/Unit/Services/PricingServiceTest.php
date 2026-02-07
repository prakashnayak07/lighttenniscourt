<?php

use App\Models\Coupon;
use App\Models\Membership;
use App\Models\PricingRule;
use App\Models\Resource;
use App\Models\User;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resource = Resource::factory()->create([
        'hourly_rate_cents' => 5000, // $50/hour
    ]);
    $this->user = User::factory()->create();
    $this->pricingService = app(PricingService::class);
});

test('calculates base price correctly', function () {
    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 15:00:00')
    );

    expect($price)->toBe(5000); // 1 hour * $50
});

test('calculates price for multiple hours', function () {
    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 17:00:00')
    );

    expect($price)->toBe(15000); // 3 hours * $50
});

test('applies peak pricing rule', function () {
    // Create peak pricing rule (50% increase on weekends)
    PricingRule::factory()->create([
        'resource_id' => $this->resource->id,
        'day_of_week' => 6, // Saturday
        'multiplier' => 1.5,
    ]);

    $saturday = Carbon::parse('next Saturday 14:00:00');
    $price = $this->pricingService->calculatePrice(
        $this->resource,
        $saturday,
        $saturday->copy()->addHour()
    );

    expect($price)->toBe(7500); // $50 * 1.5
});

test('applies membership discount', function () {
    $membership = Membership::factory()->create([
        'discount_percentage' => 20,
    ]);

    $this->user->update(['membership_id' => $membership->id]);

    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 15:00:00'),
        $this->user
    );

    expect($price)->toBe(4000); // $50 - 20% = $40
});

test('applies coupon discount', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
    ]);

    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 15:00:00'),
        null,
        $coupon
    );

    expect($price)->toBe(4500); // $50 - 10% = $45
});

test('applies fixed amount coupon', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE500',
        'discount_type' => 'fixed',
        'discount_value' => 500, // $5 off
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
    ]);

    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 15:00:00'),
        null,
        $coupon
    );

    expect($price)->toBe(4500); // $50 - $5 = $45
});

test('applies both membership and coupon discounts', function () {
    $membership = Membership::factory()->create([
        'discount_percentage' => 20,
    ]);

    $this->user->update(['membership_id' => $membership->id]);

    $coupon = Coupon::factory()->create([
        'code' => 'EXTRA10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
    ]);

    $price = $this->pricingService->calculatePrice(
        $this->resource,
        Carbon::parse('2026-02-10 14:00:00'),
        Carbon::parse('2026-02-10 15:00:00'),
        $this->user,
        $coupon
    );

    // $50 - 20% membership = $40, then - 10% coupon = $36
    expect($price)->toBe(3600);
});
