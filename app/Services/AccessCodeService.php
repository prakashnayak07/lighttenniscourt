<?php

namespace App\Services;

use App\Models\Booking;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class AccessCodeService
{
    /**
     * Generate a unique access code for a booking.
     */
    public function generateAccessCode(Booking $booking): string
    {
        $orgPrefix = strtoupper(substr($booking->organization->slug ?? 'ORG', 0, 3));
        $resourcePrefix = 'C' . $booking->resource_id;
        $datePrefix = $booking->date->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$orgPrefix}-{$resourcePrefix}-{$datePrefix}-{$random}";
    }

    /**
     * Generate QR code SVG for an access code.
     */
    public function generateQRCode(string $code): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        
        return $writer->writeString($code);
    }

    /**
     * Validate an access code and return the associated booking.
     */
    public function validateAccessCode(string $code): ?Booking
    {
        return Booking::where('access_code', $code)
            ->whereIn('status', ['confirmed', 'pending'])
            ->first();
    }

    /**
     * Mark an access code as used (check-in).
     */
    public function markAsUsed(Booking $booking): bool
    {
        if ($booking->access_code_used_at) {
            return false; // Already used
        }

        $booking->update([
            'access_code_used_at' => now(),
            'checked_in_at' => now(),
        ]);

        return true;
    }

    /**
     * Check if an access code is valid for current time.
     */
    public function isValidForCurrentTime(Booking $booking): bool
    {
        $now = now();
        $bookingDateTime = $booking->date->setTimeFromTimeString($booking->start_time);
        
        // Allow check-in 30 minutes before booking time
        $allowedFrom = $bookingDateTime->copy()->subMinutes(30);
        
        // Valid until booking end time
        $allowedUntil = $booking->date->setTimeFromTimeString($booking->end_time);

        return $now->between($allowedFrom, $allowedUntil);
    }
}
