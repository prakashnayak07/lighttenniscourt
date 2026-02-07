<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Process booking checkout.
     */
    public function checkout(Booking $booking, Request $request)
    {
        $this->authorize('update', $booking);

        $request->validate([
            'payment_method' => ['required', 'in:wallet,stripe'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $result = $this->paymentService->processBookingPayment(
                $booking,
                $request->payment_method,
                $request->coupon_code
            );

            if ($result['payment_method'] === 'stripe') {
                return redirect($result['checkout_url']);
            }

            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', 'Payment successful!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle successful Stripe payment.
     */
    public function success(Booking $booking, Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['error' => 'Invalid session.']);
        }

        try {
            $this->paymentService->handleStripeSuccess($sessionId);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', 'Payment successful!');
        } catch (\Exception $e) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle cancelled payment.
     */
    public function cancel(Booking $booking)
    {
        return redirect()
            ->route('bookings.show', $booking)
            ->with('error', 'Payment was cancelled.');
    }
}
