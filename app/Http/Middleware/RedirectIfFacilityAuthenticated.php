<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to redirect authenticated facility users away from login page.
 */
final class RedirectIfFacilityAuthenticated
{
    /**
     * Handle an incoming request.
     * Redirect to facility dashboard if facility user is already authenticated.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('facility')->check()) {
            return redirect()->route('facility.dashboard');
        }

        return $next($request);
    }
}
