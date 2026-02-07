<?php

use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\ResourceApiController;
use App\Http\Controllers\Api\WalletApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingApiController::class, 'index']);
        Route::post('/', [BookingApiController::class, 'store']);
        Route::get('/{booking}', [BookingApiController::class, 'show']);
        Route::post('/{booking}/cancel', [BookingApiController::class, 'cancel']);
    });

    // Courts/Resources
    Route::prefix('courts')->group(function () {
        Route::get('/', [ResourceApiController::class, 'index']);
        Route::get('/{resource}', [ResourceApiController::class, 'show']);
        Route::get('/{resource}/available-slots', [ResourceApiController::class, 'availableSlots']);
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletApiController::class, 'show']);
        Route::get('/transactions', [WalletApiController::class, 'transactions']);
    });
});
