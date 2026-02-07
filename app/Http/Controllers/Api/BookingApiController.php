<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BookingApiController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Get user's bookings.
     */
    public function index(Request $request)
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['resource', 'reservations', 'lineItems'])
            ->latest()
            ->paginate(20);

        return BookingResource::collection($bookings);
    }

    /**
     * Get a specific booking.
     */
    public function show(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load(['resource', 'reservations', 'lineItems', 'user']);

        return new BookingResource($booking);
    }

    /**
     * Create a new booking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'visibility' => 'nullable|in:public,private',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['user_id'] = $request->user()->id;

        $booking = $this->bookingService->createBooking($validated);

        return new BookingResource($booking);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $this->bookingService->cancelBooking($booking);

        return response()->json([
            'message' => 'Booking cancelled successfully.',
        ]);
    }
}
