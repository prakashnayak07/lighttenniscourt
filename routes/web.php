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

// Wallet Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/wallet', [App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/top-up', [App\Http\Controllers\WalletController::class, 'topUpForm'])->name('wallet.topup.form');
    Route::post('/wallet/top-up', [App\Http\Controllers\WalletController::class, 'topUp'])->name('wallet.topup');
    Route::get('/wallet/top-up/success', [App\Http\Controllers\WalletController::class, 'topUpSuccess'])->name('wallet.topup.success');
});

// Payment Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bookings/{booking}/checkout', [App\Http\Controllers\PaymentController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{booking}/payment/success', [App\Http\Controllers\PaymentController::class, 'success'])->name('bookings.payment.success');
    Route::get('/bookings/{booking}/payment/cancel', [App\Http\Controllers\PaymentController::class, 'cancel'])->name('bookings.payment.cancel');
});

// Access Code Routes (for staff)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/access-code/validate', [App\Http\Controllers\AccessCodeController::class, 'validate'])->name('api.access-code.validate');
    Route::post('/access-code/check-in', [App\Http\Controllers\AccessCodeController::class, 'checkIn'])->name('api.access-code.check-in');
});

require __DIR__.'/settings.php';
