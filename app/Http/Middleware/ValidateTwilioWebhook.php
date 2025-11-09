<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

final class ValidateTwilioWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation if disabled in config (useful for local development with ngrok)
        if (! config('services.twilio.validate_webhooks', true)) {
            \Illuminate\Support\Facades\Log::info('Twilio webhook validation skipped', [
                'env' => config('app.env'),
            ]);

            return $next($request);
        }

        $authToken = config('services.twilio.auth_token');

        if (! $authToken) {
            \Illuminate\Support\Facades\Log::warning('Twilio auth token not configured, skipping webhook validation');

            return $next($request);
        }

        $validator = new RequestValidator($authToken);

        // Get the full URL for validation
        // Note: For ngrok, make sure APP_URL matches the ngrok URL
        $url = $request->fullUrl();

        // Get all POST parameters
        $params = $request->all();

        // Get the signature from the request header
        $signature = $request->header('X-Twilio-Signature');

        if (! $signature) {
            \Illuminate\Support\Facades\Log::warning('Missing Twilio signature header', [
                'headers' => $request->headers->all(),
            ]);

            // In local development, allow requests without signature
            if (config('app.env') === 'local') {
                \Illuminate\Support\Facades\Log::info('Allowing request without signature in local environment');

                return $next($request);
            }

            return response('Unauthorized', 401);
        }

        // Validate the signature
        if (! $validator->validate($signature, $url, $params)) {
            \Illuminate\Support\Facades\Log::warning('Invalid Twilio webhook signature', [
                'url' => $url,
                'signature' => $signature,
                'params' => array_keys($params),
            ]);

            // In local development with ngrok, signature validation can fail due to URL differences
            // Allow the request to proceed but log the warning
            if (config('app.env') === 'local') {
                \Illuminate\Support\Facades\Log::info('Allowing request with invalid signature in local environment');

                return $next($request);
            }

            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
