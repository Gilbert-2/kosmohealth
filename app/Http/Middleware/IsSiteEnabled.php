<?php

namespace App\Http\Middleware;

use App\Helpers\SysHelper;
use Closure;

class IsSiteEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! config('config.website.enabled')) {
            // Return API response instead of redirecting to removed frontend
            return response()->json([
                'message' => 'KosmoHealth API Server',
                'version' => '1.0.0',
                'status' => 'running',
                'note' => 'Frontend is disabled. This is an API-only server.',
                'endpoints' => [
                    'api_docs' => '/api-docs',
                    'meetings' => '/api/meetings',
                    'auth' => '/api/auth',
                    'users' => '/api/users'
                ]
            ]);
        }

        return $next($request);
    }
}
