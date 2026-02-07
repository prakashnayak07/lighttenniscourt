<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view bookings list
    }

    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Super admin and admin/staff can view all bookings in their org
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role->isAdmin()) {
            return $user->organization_id === $booking->organization_id;
        }

        // Users can view their own bookings
        return $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create bookings
    }

    /**
     * Determine whether the user can update the booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Super admin can update any booking
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin/staff can update any booking in their org
        if ($user->role->isAdmin()) {
            return $user->organization_id === $booking->organization_id;
        }

        // Users can update their own pending bookings
        return $user->id === $booking->user_id && $booking->status === 'pending';
    }

    /**
     * Determine whether the user can delete the booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role->isAdmin()) {
            return $user->organization_id === $booking->organization_id;
        }

        // Users can cancel their own pending bookings
        return $user->id === $booking->user_id && $booking->status === 'pending';
    }
}
