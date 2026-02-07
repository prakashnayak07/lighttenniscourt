<?php

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view their own bookings', function () {
    $user = User::factory()->create();
    
    actingAs($user)
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Bookings/Index'));
});

test('guest cannot access bookings page', function () {
    get(route('bookings.index'))
        ->assertRedirect(route('login'));
});

test('user can create a booking with valid data', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->create(['status' => 'enabled']);

    $response = actingAs($user)
        ->post(route('bookings.store'), [
            'resource_id' => $resource->id,
            'date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('bookings', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
});

test('user cannot create booking with past date', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->create(['status' => 'enabled']);

    actingAs($user)
        ->post(route('bookings.store'), [
            'resource_id' => $resource->id,
            'date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])
        ->assertSessionHasErrors('date');
});

test('user cannot create booking with invalid time range', function () {
    $user = User::factory()->create();
    $resource = Resource::factory()->create(['status' => 'enabled']);

    actingAs($user)
        ->post(route('bookings.store'), [
            'resource_id' => $resource->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '15:00',
            'end_time' => '14:00', // End before start
        ])
        ->assertSessionHasErrors('end_time');
});

test('user can view their booking details', function () {
    $user = User::factory()->create();
    $booking = \App\Models\Booking::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => 
            $page->component('Bookings/Show')
                ->has('booking')
        );
});

test('user cannot view other users booking', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $booking = \App\Models\Booking::factory()->create(['user_id' => $user2->id]);

    actingAs($user1)
        ->get(route('bookings.show', $booking))
        ->assertForbidden();
});

test('user can cancel their pending booking', function () {
    $user = User::factory()->create();
    $booking = \App\Models\Booking::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    actingAs($user)
        ->post(route('bookings.cancel', $booking))
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
});

test('user cannot cancel already cancelled booking', function () {
    $user = User::factory()->create();
    $booking = \App\Models\Booking::factory()->create([
        'user_id' => $user->id,
        'status' => 'cancelled',
    ]);

    actingAs($user)
        ->post(route('bookings.cancel', $booking))
        ->assertSessionHasErrors();
});
