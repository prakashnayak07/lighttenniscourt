<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super admin can switch organizations (store in session)
            if ($user->isSuperAdmin() && $request->session()->has('current_organization_id')) {
                $organizationId = $request->session()->get('current_organization_id');
            } else {
                $organizationId = $user->organization_id;
            }

            // Store in config for easy access throughout the request
            config(['app.current_organization_id' => $organizationId]);
        }

        return $next($request);
    }
}
