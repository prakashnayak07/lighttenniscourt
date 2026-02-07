<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected WalletService $walletService,
        protected CouponService $couponService
    ) {}

    /**
     * Process booking payment.
     */
    public function processBookingPayment(
        Booking $booking,
        string $paymentMethod,
        ?string $couponCode = null
    ): array {
        return DB::transaction(function () use ($booking, $paymentMethod, $couponCode) {
            $totalCents = $booking->lineItems->sum('total_cents');

            // Apply coupon if provided
            if ($couponCode) {
                $discount = $this->couponService->applyCoupon($booking, $couponCode);
                $totalCents -= $discount;
            }

            if ($paymentMethod === 'wallet') {
                return $this->payWithWallet($booking, $totalCents);
            } elseif ($paymentMethod === 'stripe') {
                return $this->createStripeCheckout($booking, $totalCents);
            }

            throw new \Exception('Invalid payment method.');
        });
    }

    /**
     * Pay with wallet balance.
     */
    protected function payWithWallet(Booking $booking, int $totalCents): array
    {
        $wallet = $this->walletService->getOrCreateWallet($booking->user);

        if (! $this->walletService->hasSufficientBalance($wallet, $totalCents)) {
            throw new \Exception('Insufficient wallet balance.');
        }

        $this->walletService->deductFunds(
            $wallet,
            $totalCents,
            'Booking payment #'.$booking->id,
            $booking->id
        );

        $booking->update([
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'status' => 'confirmed',
        ]);

        return [
            'success' => true,
            'payment_method' => 'wallet',
            'amount_cents' => $totalCents,
        ];
    }

    /**
     * Create Stripe checkout session.
     * Note: Requires stripe/stripe-php package
     */
    protected function createStripeCheckout(Booking $booking, int $amountCents): array
    {
        // Check if Stripe is configured
        if (! class_exists('\Stripe\Stripe')) {
            throw new \Exception('Stripe is not configured. Please install stripe/stripe-php package.');
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Court Booking #'.$booking->id,
                            'description' => $booking->resource->name,
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('bookings.payment.success', ['booking' => $booking->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('bookings.payment.cancel', ['booking' => $booking->id]),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                ],
            ]);

            return [
                'success' => true,
                'payment_method' => 'stripe',
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Stripe checkout failed: '.$e->getMessage());
        }
    }

    /**
     * Handle successful Stripe payment.
     */
    public function handleStripeSuccess(string $sessionId): void
    {
        if (! class_exists('\Stripe\Stripe')) {
            throw new \Exception('Stripe is not configured.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        $bookingId = $session->metadata->booking_id;
        $booking = Booking::findOrFail($bookingId);

        $booking->update([
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_intent_id' => $session->payment_intent,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Create Stripe checkout for wallet top-up.
     */
    public function createWalletTopUpCheckout(User $user, int $amountCents): array
    {
        if (! class_exists('\Stripe\Stripe')) {
            throw new \Exception('Stripe is not configured.');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Wallet Top-up',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('wallet.topup.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('wallet.index'),
            'metadata' => [
                'user_id' => $user->id,
                'type' => 'wallet_topup',
                'amount_cents' => $amountCents,
            ],
        ]);

        return [
            'success' => true,
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    /**
     * Handle successful wallet top-up.
     */
    public function handleWalletTopUpSuccess(string $sessionId): void
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        $userId = $session->metadata->user_id;
        $amountCents = $session->metadata->amount_cents;

        $user = User::findOrFail($userId);
        $wallet = $this->walletService->getOrCreateWallet($user);

        $this->walletService->addFunds(
            $wallet,
            $amountCents,
            'stripe',
            $session->payment_intent,
            'Wallet top-up via Stripe'
        );
    }
}
