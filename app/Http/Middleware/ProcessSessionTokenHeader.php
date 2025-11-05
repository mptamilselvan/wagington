<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ProcessSessionTokenHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for X-Session-Token header and set it in the session if present
        $sessionToken = $request->header('X-Session-Token');
        
        if ($sessionToken) {
            // Validate token format and length
            if (!is_string($sessionToken) || strlen($sessionToken) > 255 || !ctype_alnum(str_replace(['-', '_'], '', $sessionToken))) {
                Log::warning('ProcessSessionTokenHeader: Invalid X-Session-Token format');
                return $next($request);
            }
            
            Log::debug('ProcessSessionTokenHeader: Found X-Session-Token header', [
                'session_token_present' => true
            ]);
            
            Session::put('guest.session_token', $sessionToken);
        } else {
            Log::debug('ProcessSessionTokenHeader: No X-Session-Token header found');
        }
        return $next($request);
    }
}