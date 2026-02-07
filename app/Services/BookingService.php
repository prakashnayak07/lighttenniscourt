<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
        protected ReservationService $reservationService,
        protected AccessCodeService $accessCodeService
    ) {}

    /**
     * Create a new booking.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $resource = Resource::findOrFail($data['resource_id']);
            $user = User::findOrFail($data['user_id']);
            $date = Carbon::parse($data['date']);
            $startTime = $data['start_time'];
            $endTime = $data['end_time'];

            // Validate availability
            if (! $this->availabilityService->isAvailable($resource, $date, $startTime, $endTime)) {
                throw new \Exception('The selected time slot is not available.');
            }

            // Calculate pricing
            $pricingData = $this->pricingService->calculateBookingPrice(
                $resource,
                $date,
                $startTime,
                $endTime,
                $user
            );

            // Create booking
            $booking = Booking::create([
                'organization_id' => $resource->organization_id,
                'user_id' => $user->id,
                'resource_id' => $resource->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'visibility' => $data['visibility'] ?? 'private',
                'notes' => $data['notes'] ?? null,
            ]);

            // Create reservation (time slot)
            $this->reservationService->createReservation($booking, $date, $startTime, $endTime);

            // Create line items
            $lineItems = $this->pricingService->generateLineItems($booking, $pricingData);
            foreach ($lineItems as $item) {
                $booking->lineItems()->create($item);
            }

            $booking = $booking->fresh(['reservations', 'lineItems', 'resource', 'user']);

            // Generate access code for confirmed bookings
            if ($booking->status === 'confirmed' || $booking->payment_status === 'paid') {
                $accessCode = $this->accessCodeService->generateAccessCode($booking);
                $booking->update(['access_code' => $accessCode]);
            }

            // Send confirmation email
            $user->notify(new BookingConfirmed($booking));

            return $booking;
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(Booking $booking, bool $issueRefund = true): bool
    {
        return DB::transaction(function () use ($booking, $issueRefund) {
            // Update booking status
            $booking->update(['status' => 'cancelled']);

            $refundAmount = 0;

            // Issue refund if paid and refund requested
            if ($issueRefund && $booking->payment_status === 'paid') {
                $refundAmount = $booking->lineItems->sum('total_cents');
                $this->processRefund($booking);
            }

            // Send cancellation email
            $booking->user->notify(new BookingCancelled($booking, $refundAmount));

            return true;
        });
    }

    /**
     * Process refund for cancelled booking.
     */
    protected function processRefund(Booking $booking): void
    {
        $totalPaid = $booking->lineItems->sum('total_cents');

        if ($booking->payment_method === 'wallet') {
            // Refund to wallet
            $walletService = app(\App\Services\WalletService::class);
            $wallet = $walletService->getOrCreateWallet($booking->user);
            $walletService->refund(
                $wallet,
                $totalPaid,
                $booking->id,
                'Booking cancelled'
            );

            $booking->update(['payment_status' => 'refunded']);
        } elseif ($booking->payment_method === 'stripe') {
            // Refund via Stripe would be implemented here
            // This would call Stripe API to create a refund
            $booking->update(['payment_status' => 'refund_pending']);
        }
    }

    /**
     * Confirm a booking (after payment).
     */
    public function confirmBooking(Booking $booking): bool
    {
        return $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Mark booking as completed.
     */
    public function completeBooking(Booking $booking): bool
    {
        return $booking->update(['status' => 'completed']);
    }

    /**
     * Mark booking as no-show.
     */
    public function markAsNoShow(Booking $booking): bool
    {
        return $booking->update(['status' => 'no_show']);
    }

    /**
     * Check in a booking.
     */
    public function checkIn(Booking $booking): bool
    {
        return $booking->update(['check_in_at' => now()]);
    }

    /**
     * Get booking summary with pricing.
     */
    public function getBookingSummary(Booking $booking): array
    {
        $total = $booking->lineItems->sum('total_cents');

        return [
            'booking' => $booking,
            'total_cents' => $total,
            'total_formatted' => '$'.number_format($total / 100, 2),
            'line_items' => $booking->lineItems,
        ];
    }
}
