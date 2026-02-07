<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WalletApiController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Get user's wallet.
     */
    public function show(Request $request)
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());
        $wallet->load('transactions');

        return new WalletResource($wallet);
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(Request $request)
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());
        
        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(50);

        return response()->json($transactions);
    }
}
