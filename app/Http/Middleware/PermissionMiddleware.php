<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        // Special case for whiteboard sharing - allow if user has whiteboard-share permission
        if ($permission === 'whiteboard-share' && $request->route('whiteboard')) {
            if (Auth::check() && (Auth::user()->can('whiteboard-share') || 
                Auth::user()->id === $request->route('whiteboard')->user_id)) {
                return $next($request);
            }
        }
        
        // Special case for whiteboard access - allow if user has whiteboard-share permission
        if ($permission === 'whiteboard-access' && $request->route('whiteboard') && $request->isMethod('get')) {
            if (Auth::check() && (Auth::user()->can('whiteboard-share') || 
                Auth::user()->id === $request->route('whiteboard')->user_id)) {
                return $next($request);
            }
        }
        
        // Original board sharing logic
        if ($permission === 'board-access' && $request->route('board') && $request->isMethod('get')) {
            if (Auth::check() && Auth::user()->can('board-share')) {
                return $next($request);
            }
        }
        
        // Check if user is authenticated
        if (Auth::guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        foreach ($permissions as $permission) {
            if (Auth::user()->can($permission)) {
                return $next($request);
            }
        }

        throw UnauthorizedException::forPermissions($permissions);
    }
}

