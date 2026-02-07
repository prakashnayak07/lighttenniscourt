<?php

use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->walletService = app(WalletService::class);
});

test('creates wallet for user if not exists', function () {
    expect($this->user->wallet)->toBeNull();

    $wallet = $this->walletService->getOrCreateWallet($this->user);

    expect($wallet)->toBeInstanceOf(Wallet::class)
        ->and($wallet->user_id)->toBe($this->user->id)
        ->and($wallet->balance_cents)->toBe(0);
});

test('returns existing wallet if already exists', function () {
    $existingWallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance_cents' => 10000,
    ]);

    $wallet = $this->walletService->getOrCreateWallet($this->user);

    expect($wallet->id)->toBe($existingWallet->id)
        ->and($wallet->balance_cents)->toBe(10000);
});

test('can add funds to wallet', function () {
    $wallet = $this->walletService->getOrCreateWallet($this->user);
    
    $this->walletService->addFunds($wallet, 5000, 'stripe', 'ch_123');

    $wallet->refresh();

    expect($wallet->balance_cents)->toBe(5000)
        ->and($wallet->transactions)->toHaveCount(1)
        ->and($wallet->transactions->first()->amount_cents)->toBe(5000)
        ->and($wallet->transactions->first()->type)->toBe('credit');
});

test('can deduct funds from wallet', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance_cents' => 10000,
    ]);

    $this->walletService->deductFunds($wallet, 3000, 'Booking payment');

    $wallet->refresh();

    expect($wallet->balance_cents)->toBe(7000)
        ->and($wallet->transactions)->toHaveCount(1)
        ->and($wallet->transactions->first()->amount_cents)->toBe(-3000)
        ->and($wallet->transactions->first()->type)->toBe('debit');
});

test('cannot deduct more than available balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance_cents' => 1000,
    ]);

    expect(fn() => $this->walletService->deductFunds($wallet, 5000, 'Test'))
        ->toThrow(\Exception::class, 'Insufficient balance');
});

test('records correct balance after transaction', function () {
    $wallet = $this->walletService->getOrCreateWallet($this->user);
    
    $this->walletService->addFunds($wallet, 10000, 'stripe', 'ch_123');
    $this->walletService->deductFunds($wallet, 3000, 'Booking');
    $this->walletService->addFunds($wallet, 2000, 'stripe', 'ch_456');

    $wallet->refresh();

    expect($wallet->balance_cents)->toBe(9000)
        ->and($wallet->transactions)->toHaveCount(3);
    
    $lastTransaction = $wallet->transactions->sortByDesc('created_at')->first();
    expect($lastTransaction->balance_after_cents)->toBe(9000);
});
