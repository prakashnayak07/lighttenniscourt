<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->id)->toBe($user->id);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ])->assertSessionHasErrors();

    expect(auth()->check())->toBeFalse();
});

test('user can logout', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});

test('admin can access admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('customer cannot access admin panel', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    actingAs($customer)
        ->get('/admin')
        ->assertForbidden();
});

test('users can only see their own organization data', function () {
    $org1 = \App\Models\Organization::factory()->create();
    $org2 = \App\Models\Organization::factory()->create();

    $user1 = User::factory()->create(['organization_id' => $org1->id]);
    $user2 = User::factory()->create(['organization_id' => $org2->id]);

    $booking1 = \App\Models\Booking::factory()->create(['user_id' => $user1->id]);
    $booking2 = \App\Models\Booking::factory()->create(['user_id' => $user2->id]);

    actingAs($user1);

    $bookings = \App\Models\Booking::all();

    expect($bookings)->toHaveCount(1)
        ->and($bookings->first()->id)->toBe($booking1->id);
});
