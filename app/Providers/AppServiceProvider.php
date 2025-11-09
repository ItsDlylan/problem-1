<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Twilio\Rest\Client;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Twilio Client
        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );
        });
    }

    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootPasswordDefaults();
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
    }

    private function bootPasswordDefaults(): void
    {
        Password::defaults(fn () => app()->isLocal() || app()->runningUnitTests() ? Password::min(12)->max(255) : Password::min(12)->max(255)->uncompromised());
    }
}
