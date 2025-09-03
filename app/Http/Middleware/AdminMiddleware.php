<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated user attempted admin access', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $user = Auth::user();

        // Check if user has admin role
        if (!$this->isAdmin($user)) {
            Log::warning('Non-admin user attempted admin access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role ?? 'unknown',
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        }

        // Log admin access for audit trail
        Log::info('Admin access granted', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip()
        ]);

        return $next($request);
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($user): bool
    {
        // Check role field
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        // Check if user has admin permission (if using Spatie permissions)
        if (method_exists($user, 'can') && $user->can('admin-access')) {
            return true;
        }

        // Check if user has admin role (if using Spatie roles)
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Check if user is in admin table or has admin flag
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return false;
    }
}
