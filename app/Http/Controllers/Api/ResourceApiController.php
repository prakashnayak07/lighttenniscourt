<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ResourceApiController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Get all available courts.
     */
    public function index(Request $request)
    {
        $resources = Resource::where('status', 'enabled')
            ->get();

        return ResourceResource::collection($resources);
    }

    /**
     * Get a specific court.
     */
    public function show(Resource $resource)
    {
        return new ResourceResource($resource);
    }

    /**
     * Get available slots for a court.
     */
    public function availableSlots(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:240',
        ]);

        $date = Carbon::parse($validated['date']);
        $duration = $validated['duration'] ?? $resource->time_block_minutes;

        $slots = $this->availabilityService->getAvailableSlots($resource, $date, $duration);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'court' => $resource->name,
            'slots' => $slots->values(),
        ]);
    }
}
