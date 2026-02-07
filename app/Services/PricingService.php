<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PricingRule;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate total price for a booking.
     */
    public function calculateBookingPrice(
        Resource $resource,
        Carbon $date,
        string $startTime,
        string $endTime,
        ?User $user = null
    ): array {
        $basePrice = $this->getBasePrice($resource, $date, $startTime, $endTime);
        $discount = 0;
        $discountReason = null;

        // Apply membership discount if applicable
        if ($user) {
            $membership = $user->memberships()
                ->where('status', 'active')
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->first();

            if ($membership && $membership->membershipType->court_fee_discount_percent > 0) {
                $discount = (int) (($basePrice * $membership->membershipType->court_fee_discount_percent) / 100);
                $discountReason = 'Membership: '.$membership->membershipType->name;
            }
        }

        $finalPrice = $basePrice - $discount;

        return [
            'base_price_cents' => $basePrice,
            'discount_cents' => $discount,
            'final_price_cents' => $finalPrice,
            'discount_reason' => $discountReason,
        ];
    }

    /**
     * Get base price from pricing rules.
     */
    protected function getBasePrice(
        Resource $resource,
        Carbon $date,
        string $startTime,
        string $endTime
    ): int {
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Find matching pricing rule for specific resource
        $pricingRule = PricingRule::where('resource_id', $resource->id)
            ->where('is_active', true)
            ->where('day_of_week_start', '<=', $dayOfWeek)
            ->where('day_of_week_end', '>=', $dayOfWeek)
            ->where('time_start', '<=', $startTime)
            ->where('time_end', '>=', $endTime)
            ->first();

        // If no specific rule, try organization-wide rule
        if (! $pricingRule) {
            $pricingRule = PricingRule::where('organization_id', $resource->organization_id)
                ->whereNull('resource_id')
                ->where('is_active', true)
                ->where('day_of_week_start', '<=', $dayOfWeek)
                ->where('day_of_week_end', '>=', $dayOfWeek)
                ->where('time_start', '<=', $startTime)
                ->where('time_end', '>=', $endTime)
                ->first();
        }

        if ($pricingRule) {
            return $pricingRule->price_cents;
        }

        // Default fallback price (could be configurable)
        return 5000; // $50.00
    }

    /**
     * Generate line items for a booking.
     */
    public function generateLineItems(Booking $booking, array $pricingData): array
    {
        $lineItems = [];

        // Court fee
        $lineItems[] = [
            'description' => 'Court Rental',
            'quantity' => 1,
            'unit_price_cents' => $pricingData['base_price_cents'],
            'total_cents' => $pricingData['base_price_cents'],
            'type' => 'court_fee',
        ];

        // Discount
        if ($pricingData['discount_cents'] > 0) {
            $lineItems[] = [
                'description' => 'Discount: '.$pricingData['discount_reason'],
                'quantity' => 1,
                'unit_price_cents' => -$pricingData['discount_cents'],
                'total_cents' => -$pricingData['discount_cents'],
                'type' => 'discount',
            ];
        }

        return $lineItems;
    }

    /**
     * Calculate total from line items.
     */
    public function calculateTotal(array $lineItems): int
    {
        return collect($lineItems)->sum('total_cents');
    }
}
