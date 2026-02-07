<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    /**
     * Determine whether the user can view any resources.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view resources
    }

    /**
     * Determine whether the user can view the resource.
     */
    public function view(User $user, Resource $resource): bool
    {
        return $user->isSuperAdmin() || $user->organization_id === $resource->organization_id;
    }

    /**
     * Determine whether the user can create resources.
     */
    public function create(User $user): bool
    {
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can update the resource.
     */
    public function update(User $user, Resource $resource): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->role->isAdmin() && $user->organization_id === $resource->organization_id;
    }

    /**
     * Determine whether the user can delete the resource.
     */
    public function delete(User $user, Resource $resource): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canManageOrganization() && $user->organization_id === $resource->organization_id;
    }
}
