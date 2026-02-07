<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('can authenticate with valid token', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
            ],
        ]);
});

test('cannot access API without token', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});

test('cannot access API with invalid token', function () {
    $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('can list available courts', function () {
    \App\Models\Resource::factory()->count(3)->create(['status' => 'enabled']);

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/courts')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can get court details', function () {
    $court = \App\Models\Resource::factory()->create(['status' => 'enabled']);

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson("/api/courts/{$court->id}")
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $court->id,
                'name' => $court->name,
            ],
        ]);
});

test('can get available slots for a court', function () {
    $court = \App\Models\Resource::factory()->create(['status' => 'enabled']);
    $date = now()->addDay()->format('Y-m-d');

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson("/api/courts/{$court->id}/available-slots?date={$date}")
        ->assertOk()
        ->assertJsonStructure([
            'date',
            'court',
            'slots',
        ]);
});

test('can create booking via API', function () {
    $court = \App\Models\Resource::factory()->create(['status' => 'enabled']);

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/bookings', [
            'resource_id' => $court->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'access_code',
                'resource',
            ],
        ]);
});

test('can list user bookings via API', function () {
    \App\Models\Booking::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can get wallet balance via API', function () {
    $wallet = \App\Models\Wallet::factory()->create([
        'user_id' => $this->user->id,
        'balance_cents' => 10000,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/wallet')
        ->assertOk()
        ->assertJson([
            'data' => [
                'balance_cents' => 10000,
            ],
        ]);
});
