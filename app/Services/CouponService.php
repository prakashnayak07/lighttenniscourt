<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponService
{
    /**
     * Validate and apply coupon to booking.
     */
    public function applyCoupon(Booking $booking, string $code): int
    {
        $coupon = Coupon::where('code', $code)
            ->where('organization_id', $booking->organization_id)
            ->first();

        if (! $coupon) {
            throw new \Exception('Invalid coupon code.');
        }

        // Validate coupon
        $this->validateCoupon($coupon);

        // Calculate discount
        $discount = $this->calculateDiscount($booking, $coupon);

        // Create line item for discount
        $booking->lineItems()->create([
            'description' => 'Coupon: '.$coupon->code,
            'quantity' => 1,
            'unit_price_cents' => -$discount,
            'total_cents' => -$discount,
            'type' => 'coupon_discount',
        ]);

        // Increment usage
        $coupon->increment('usage_count');

        return $discount;
    }

    /**
     * Validate coupon.
     */
    protected function validateCoupon(Coupon $coupon): void
    {
        if (! $coupon->is_active) {
            throw new \Exception('This coupon is no longer active.');
        }

        if ($coupon->valid_until && Carbon::parse($coupon->valid_until)->isPast()) {
            throw new \Exception('This coupon has expired.');
        }

        if ($coupon->max_uses && $coupon->usage_count >= $coupon->max_uses) {
            throw new \Exception('This coupon has reached its usage limit.');
        }
    }

    /**
     * Calculate discount amount.
     */
    protected function calculateDiscount(Booking $booking, Coupon $coupon): int
    {
        $total = $booking->lineItems->where('type', 'court_fee')->sum('total_cents');

        if ($coupon->discount_type === 'percentage') {
            return (int) (($total * $coupon->discount_value) / 100);
        } else {
            // Fixed amount discount
            return min($coupon->discount_value, $total);
        }
    }

    /**
     * Check if coupon is valid.
     */
    public function isValid(string $code, int $organizationId): bool
    {
        try {
            $coupon = Coupon::where('code', $code)
                ->where('organization_id', $organizationId)
                ->first();

            if (! $coupon) {
                return false;
            }

            $this->validateCoupon($coupon);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get coupon details.
     */
    public function getCoupon(string $code, int $organizationId): ?Coupon
    {
        return Coupon::where('code', $code)
            ->where('organization_id', $organizationId)
            ->first();
    }
}
