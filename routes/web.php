<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Booking Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bookings', [App\Http\Controllers\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [App\Http\Controllers\BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [App\Http\Controllers\BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [App\Http\Controllers\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [App\Http\Controllers\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/check-in', [App\Http\Controllers\BookingController::class, 'checkIn'])->name('bookings.check-in');
    Route::get('/api/available-slots', [App\Http\Controllers\BookingController::class, 'availableSlots'])->name('api.available-slots');
});

require __DIR__.'/settings.php';
