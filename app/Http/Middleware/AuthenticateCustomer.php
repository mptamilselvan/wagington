<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
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
            return redirect()->route('customer.login')->with('error', 'Please log in to access this page.');
        }

        $user = Auth::user();

        // Check if user account is soft deleted (extra safety check)
        if ($user->trashed()) {
            Auth::logout();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Your account has been deleted. Please contact support if you need assistance.'], 401);
            }
            return redirect()->route('customer.login')->with('error', 'Your account has been deleted. Please contact support if you need assistance.');
        }

        // Check if user has customer role
        if (!$user->hasRole('customer')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied. Customer access required.'], 403);
            }
            return redirect()->route('customer.login')->with('error', 'Access denied. Customer access required.');
        }

        return $next($request);
    }
}