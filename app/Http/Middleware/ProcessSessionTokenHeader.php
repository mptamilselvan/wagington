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
            Log::info('ProcessSessionTokenHeader: Found X-Session-Token header', [
                'session_token_present' => true,
                'session_id' => Session::getId(),
            ]);
            
            Session::put('guest.session_token', $sessionToken);
        } else {
            Log::info('ProcessSessionTokenHeader: No X-Session-Token header found', [
                'session_id' => Session::getId(),
            ]);
        }
        return $next($request);
    }
}