<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Don't apply scope for super admin
        if ($user && $user->isSuperAdmin()) {
            return;
        }

        // Apply organization filter
        $organizationId = config('app.current_organization_id') ?? $user?->organization_id;

        if ($organizationId) {
            $builder->where($model->getTable().'.organization_id', $organizationId);
        }
    }
}
