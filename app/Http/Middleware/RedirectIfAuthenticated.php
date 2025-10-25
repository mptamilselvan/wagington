<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated, redirect based on their role
        if (Auth::check()) {
            if (Auth::user()->hasRole('customer')) {
                return redirect()->route('customer.dashboard');
            }
            
            if (Auth::user()->hasRole('admin')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}