<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can view a user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role->isAdmin()) {
            return $user->organization_id === $model->organization_id;
        }

        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can update users in their org (but not super_admin)
        if ($user->isAdmin() && $user->organization_id === $model->organization_id) {
            return ! $model->isSuperAdmin();
        }

        // Users can update themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can delete users in their org (but not super_admin or other admins)
        if ($user->isAdmin() && $user->organization_id === $model->organization_id) {
            return ! $model->isSuperAdmin() && ! $model->isAdmin();
        }

        return false;
    }
}
