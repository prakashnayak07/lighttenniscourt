<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Display wallet and transaction history.
     */
    public function index()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return inertia('Wallet/Index', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'formatted_balance' => $this->walletService->getFormattedBalance($wallet),
        ]);
    }

    /**
     * Show top-up form.
     */
    public function topUpForm()
    {
        $wallet = $this->walletService->getOrCreateWallet(auth()->user());

        return inertia('Wallet/TopUp', [
            'wallet' => $wallet,
            'formatted_balance' => $this->walletService->getFormattedBalance($wallet),
        ]);
    }

    /**
     * Process wallet top-up.
     */
    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:100000'], // $10 to $1000
        ]);

        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $result = $paymentService->createWalletTopUpCheckout(
                auth()->user(),
                $request->amount
            );

            return redirect($result['checkout_url']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle successful top-up.
     */
    public function topUpSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()->route('wallet.index')->withErrors(['error' => 'Invalid session.']);
        }

        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentService->handleWalletTopUpSuccess($sessionId);

            return redirect()
                ->route('wallet.index')
                ->with('success', 'Wallet topped up successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('wallet.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
