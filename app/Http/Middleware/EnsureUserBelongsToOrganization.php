<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToOrganization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin can access all organizations
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if route has organization parameter
        $organizationId = $request->route('organization');

        if ($organizationId && $user && $user->organization_id != $organizationId) {
            abort(403, 'Unauthorized access to this organization.');
        }

        return $next($request);
    }
}
