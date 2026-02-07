<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use App\Notifications\WalletTopUpSuccess;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet for user.
     */
    public function getOrCreateWallet(User $user): UserWallet
    {
        $organizationId = $user->organization_id
            ?? Organization::query()->value('id');

        if ($organizationId === null) {
            throw new \InvalidArgumentException(
                'Cannot create wallet: user has no organization and no organizations exist. Create an organization and assign it to the user, or create an organization first.'
            );
        }

        return UserWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'organization_id' => $organizationId,
                'balance_cents' => 0,
            ]
        );
    }

    /**
     * Add funds to wallet.
     */
    public function addFunds(
        UserWallet $wallet,
        int $amountCents,
        string $paymentMethod,
        ?string $paymentIntentId = null,
        ?string $description = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amountCents, $paymentMethod, $paymentIntentId, $description) {
            // Update wallet balance
            $wallet->increment('balance_cents', $amountCents);

            // Log transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'amount_cents' => $amountCents,
                'type' => 'credit',
                'payment_method' => $paymentMethod,
                'payment_intent_id' => $paymentIntentId,
                'description' => $description ?? 'Wallet top-up',
                'balance_after_cents' => $wallet->balance_cents,
            ]);

            // Send notification email
            $wallet->user->notify(new WalletTopUpSuccess($transaction));

            return $transaction;
        });
    }

    /**
     * Deduct funds from wallet.
     */
    public function deductFunds(
        UserWallet $wallet,
        int $amountCents,
        string $description,
        ?int $bookingId = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amountCents, $description, $bookingId) {
            if ($wallet->balance_cents < $amountCents) {
                throw new \Exception('Insufficient wallet balance.');
            }

            // Update wallet balance
            $wallet->decrement('balance_cents', $amountCents);

            // Log transaction
            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'booking_id' => $bookingId,
                'amount_cents' => -$amountCents,
                'type' => 'debit',
                'payment_method' => 'wallet',
                'description' => $description,
                'balance_after_cents' => $wallet->balance_cents,
            ]);
        });
    }

    /**
     * Refund to wallet.
     */
    public function refund(
        UserWallet $wallet,
        int $amountCents,
        int $bookingId,
        string $reason
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amountCents, $bookingId, $reason) {
            // Update wallet balance
            $wallet->increment('balance_cents', $amountCents);

            // Log transaction
            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'booking_id' => $bookingId,
                'amount_cents' => $amountCents,
                'type' => 'refund',
                'payment_method' => 'wallet',
                'description' => 'Refund: '.$reason,
                'balance_after_cents' => $wallet->balance_cents,
            ]);
        });
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(UserWallet $wallet, int $amountCents): bool
    {
        return $wallet->balance_cents >= $amountCents;
    }

    /**
     * Get wallet transaction history.
     */
    public function getTransactionHistory(UserWallet $wallet, int $limit = 50)
    {
        return $wallet->transactions()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get wallet balance formatted.
     */
    public function getFormattedBalance(UserWallet $wallet): string
    {
        return '$'.number_format($wallet->balance_cents / 100, 2);
    }
}
