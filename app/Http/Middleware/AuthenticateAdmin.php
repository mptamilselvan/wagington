<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied. Admin access required.'], 403);
            }
            return redirect()->route('login')->with('error', 'Access denied. Admin access required.');
            // return redirect()->route('home')->with('error', 'Access denied. Admin access required.');
        }

        return $next($request);
    }
}