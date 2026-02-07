<?php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view their wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'balance_cents' => 10000,
    ]);

    actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => 
            $page->component('Wallet/Index')
                ->has('wallet')
                ->where('wallet.balance_cents', 10000)
        );
});

test('wallet is created automatically if not exists', function () {
    $user = User::factory()->create();

    expect($user->wallet)->toBeNull();

    actingAs($user)
        ->get(route('wallet.index'))
        ->assertOk();

    $user->refresh();
    expect($user->wallet)->not->toBeNull()
        ->and($user->wallet->balance_cents)->toBe(0);
});

test('user can view transaction history', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    
    \App\Models\WalletTransaction::factory()->count(5)->create([
        'wallet_id' => $wallet->id,
    ]);

    actingAs($user)
        ->get(route('wallet.transactions'))
        ->assertOk()
        ->assertInertia(fn ($page) => 
            $page->component('Wallet/Transactions')
                ->has('transactions.data', 5)
        );
});

test('user can initiate wallet top-up', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('wallet.topup'), [
            'amount' => 5000, // $50
        ])
        ->assertRedirect();
});

test('user cannot top-up with invalid amount', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('wallet.topup'), [
            'amount' => -1000, // Negative amount
        ])
        ->assertSessionHasErrors('amount');
});

test('user cannot top-up with amount below minimum', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('wallet.topup'), [
            'amount' => 100, // Less than minimum (usually $5 = 500 cents)
        ])
        ->assertSessionHasErrors('amount');
});
