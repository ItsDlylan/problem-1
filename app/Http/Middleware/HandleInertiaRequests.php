<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $quote = Inspiring::quotes()->random();
        assert(is_string($quote));

        [$message, $author] = str($quote)->explode('-');

        // Get the authenticated user from any guard (patient, facility, or default)
        // Also determine which guard was used to identify user type
        $patient = $request->user('patient');
        $facility = $request->user('facility');
        $user = $patient ?? $facility ?? $request->user();
        
        // Determine user type based on which guard authenticated the user
        $userType = null;
        if ($patient !== null) {
            $userType = 'patient';
        } elseif ($facility !== null) {
            $userType = 'facility';
        } elseif ($user !== null) {
            $userType = 'facility'; // Default guard is for facility users
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => mb_trim((string) $message), 'author' => mb_trim((string) $author)],
            'auth' => [
                'user' => $user,
                'userType' => $userType, // 'patient', 'facility', or null
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
