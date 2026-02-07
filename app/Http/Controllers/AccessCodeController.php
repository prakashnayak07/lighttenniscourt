<?php

namespace App\Http\Controllers;

use App\Services\AccessCodeService;
use Illuminate\Http\Request;

class AccessCodeController extends Controller
{
    public function __construct(
        protected AccessCodeService $accessCodeService
    ) {
    }

    /**
     * Validate an access code.
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $booking = $this->accessCodeService->validateAccessCode($request->code);

        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired access code.',
            ], 404);
        }

        // Check if code is valid for current time
        if (!$this->accessCodeService->isValidForCurrentTime($booking)) {
            return response()->json([
                'valid' => false,
                'message' => 'Access code is not valid at this time.',
                'booking' => [
                    'date' => $booking->date->format('Y-m-d'),
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                ],
            ], 403);
        }

        // Check if already used
        if ($booking->access_code_used_at) {
            return response()->json([
                'valid' => false,
                'message' => 'Access code has already been used.',
                'used_at' => $booking->access_code_used_at->format('Y-m-d H:i:s'),
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'booking' => [
                'id' => $booking->id,
                'court' => $booking->resource->name,
                'user' => $booking->user->name,
                'date' => $booking->date->format('Y-m-d'),
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => $booking->status,
            ],
        ]);
    }

    /**
     * Mark access code as used (check-in).
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $booking = $this->accessCodeService->validateAccessCode($request->code);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid access code.',
            ], 404);
        }

        $success = $this->accessCodeService->markAsUsed($booking);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Access code has already been used.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful.',
            'booking' => [
                'id' => $booking->id,
                'court' => $booking->resource->name,
                'checked_in_at' => $booking->checked_in_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
