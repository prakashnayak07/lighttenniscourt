<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Models\Booking;
use App\Models\Resource;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with(['resource', 'user', 'reservations'])
            ->when(! auth()->user()->role->isAdmin(), function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(20);

        return inertia('Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create()
    {
        $this->authorize('create', Booking::class);

        $resources = Resource::where('status', 'enabled')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return inertia('Bookings/Create', [
            'resources' => $resources,
        ]);
    }

    /**
     * Store a newly created booking.
     */
    public function store(CreateBookingRequest $request)
    {
        $this->authorize('create', Booking::class);

        try {
            $booking = $this->bookingService->createBooking([
                ...$request->validated(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', 'Booking created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load(['resource', 'user', 'reservations', 'lineItems', 'participants']);

        $summary = $this->bookingService->getBookingSummary($booking);

        return inertia('Bookings/Show', [
            'booking' => $booking,
            'summary' => $summary,
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Booking $booking)
    {
        $this->authorize('delete', $booking);

        try {
            $this->bookingService->cancelBooking($booking);

            return back()->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Check in to a booking.
     */
    public function checkIn(Booking $booking)
    {
        $this->authorize('update', $booking);

        try {
            $this->bookingService->checkIn($booking);

            return back()->with('success', 'Checked in successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available time slots for a resource on a date.
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'resource_id' => ['required', 'exists:resources,id'],
            'date' => ['required', 'date'],
        ]);

        $resource = Resource::findOrFail($request->resource_id);
        $date = Carbon::parse($request->date);

        $slots = $this->availabilityService->getAvailableSlots($resource, $date);

        return response()->json([
            'slots' => $slots,
        ]);
    }
}
