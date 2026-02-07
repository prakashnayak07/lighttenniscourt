<?php

use App\Models\Booking;
use App\Models\ClubMembershipType;
use App\Models\Reservation;
use App\Models\UserClubMembership;
use App\Models\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    $wallet = UserWallet::query()
        ->where('user_id', $user->id)
        ->first();

    $membership = UserClubMembership::query()
        ->with('membershipType')
        ->where('user_id', $user->id)
        ->orderByDesc('valid_from')
        ->first();

    $upcomingReservations = Reservation::query()
        ->whereHas('booking', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['resource', 'booking'])
        ->whereDate('reservation_date', '>=', now()->toDateString())
        ->orderBy('reservation_date')
        ->orderBy('start_time')
        ->limit(5)
        ->get()
        ->map(function (Reservation $reservation) {
            return [
                'id' => $reservation->id,
                'booking_id' => $reservation->booking_id,
                'resource' => $reservation->resource?->name,
                'date' => $reservation->reservation_date?->format('Y-m-d'),
                'start_time' => $reservation->start_time ? substr($reservation->start_time, 0, 5) : null,
                'end_time' => $reservation->end_time ? substr($reservation->end_time, 0, 5) : null,
                'status' => $reservation->booking?->status,
                'payment_status' => $reservation->booking?->payment_status,
            ];
        });

    $recentBookings = Booking::query()
        ->with(['resource', 'reservations'])
        ->where('user_id', $user->id)
        ->orderByDesc('id')
        ->limit(5)
        ->get()
        ->map(function (Booking $booking) {
            $reservation = $booking->reservations->sortByDesc('reservation_date')->first();

            return [
                'id' => $booking->id,
                'resource' => $booking->resource?->name,
                'date' => $reservation?->reservation_date?->format('Y-m-d'),
                'start_time' => $reservation?->start_time ? substr($reservation->start_time, 0, 5) : null,
                'end_time' => $reservation?->end_time ? substr($reservation->end_time, 0, 5) : null,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'created_at' => $booking->created_at?->format('Y-m-d'),
            ];
        });

    $bookingsChart = collect(range(6, 0))->map(function (int $daysAgo) use ($user) {
        $date = now()->subDays($daysAgo)->startOfDay();

        return [
            'label' => $date->format('D'),
            'full' => $date->format('M j'),
            'count' => Booking::query()
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count(),
        ];
    })->values()->all();

    return Inertia::render('dashboard', [
        'stats' => [
            'total_bookings' => Booking::query()->where('user_id', $user->id)->count(),
            'upcoming_count' => $upcomingReservations->count(),
            'wallet_balance_cents' => $wallet?->balance_cents ?? 0,
        ],
        'membership' => $membership ? [
            'name' => $membership->membershipType?->name,
            'status' => $membership->status,
            'valid_from' => $membership->valid_from?->format('Y-m-d'),
            'valid_until' => $membership->valid_until?->format('Y-m-d'),
        ] : null,
        'upcomingBookings' => $upcomingReservations,
        'recentBookings' => $recentBookings,
        'bookingsChart' => $bookingsChart,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('membership', function (Request $request) {
    $user = $request->user();
    $organizationId = config('app.current_organization_id') ?? $user->organization_id;

    $membership = UserClubMembership::query()
        ->with('membershipType')
        ->where('user_id', $user->id)
        ->orderByDesc('valid_from')
        ->first();

    $membershipTypes = ClubMembershipType::query()
        ->where('organization_id', $organizationId)
        ->where('is_public', true)
        ->orderBy('price_cents')
        ->get()
        ->map(function (ClubMembershipType $type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'price_cents' => $type->price_cents,
                'billing_cycle' => $type->billing_cycle,
                'booking_window_days' => $type->booking_window_days,
                'max_active_bookings' => $type->max_active_bookings,
                'court_fee_discount_percent' => $type->court_fee_discount_percent,
            ];
        });

    return Inertia::render('membership', [
        'membership' => $membership ? [
            'name' => $membership->membershipType?->name,
            'status' => $membership->status,
            'valid_from' => $membership->valid_from?->format('Y-m-d'),
            'valid_until' => $membership->valid_until?->format('Y-m-d'),
        ] : null,
        'membershipTypes' => $membershipTypes,
    ]);
})->middleware(['auth', 'verified'])->name('membership.index');

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
