<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootFortifyDefaults();
        $this->bootRateLimitingDefaults();
        $this->removeFortifyLoginRoutes();
    }

    private function bootFortifyDefaults(): void
    {
        Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/show'));
        Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/create'));
    }

    /**
     * Remove Fortify's default login routes since we use custom authentication.
     * Patients use /patient/login, facility users use /facility/login.
     * We keep Fortify for two-factor authentication features only.
     */
    private function removeFortifyLoginRoutes(): void
    {
        // Override Fortify's login routes by registering our own that return 404
        // This prevents Fortify's login routes from being accessible
        Route::middleware('web')->group(function () {
            Route::get('login', fn () => abort(404))->name('login');
            Route::post('login', fn () => abort(404))->name('login.store');
        });
    }

    private function bootRateLimitingDefaults(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->string('email')->value().$request->ip()));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
