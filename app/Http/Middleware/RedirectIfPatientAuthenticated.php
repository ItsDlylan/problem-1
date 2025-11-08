<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to redirect authenticated patients away from login/register pages.
 */
final class RedirectIfPatientAuthenticated
{
    /**
     * Handle an incoming request.
     * Redirect to patient dashboard if patient is already authenticated.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('patient')->check()) {
            return redirect()->route('patient.dashboard');
        }

        return $next($request);
    }
}
