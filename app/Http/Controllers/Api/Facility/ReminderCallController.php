<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

use App\Services\TwilioCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller for facility reminder call API endpoints.
 * Handles initiating reminder calls to patients.
 */
final readonly class ReminderCallController
{
    public function __construct(
        private TwilioCallService $twilioCallService,
    ) {
    }

    /**
     * Initiate a reminder call to +14153519358.
     * When answered, the system will find the patient by phone number
     * and deliver a reminder about their last created appointment.
     *
     * @return JsonResponse
     */
    public function initiate(): JsonResponse
    {
        try {
            $targetNumber = '+17029863959';
            
            // Generate webhook URL - use url() helper which respects APP_URL
            // Make sure APP_URL in .env is set to your public URL (e.g., ngrok URL for local dev)
            // $webhookUrl = url('/api/twilio/voice/reminder');
            $webhookUrl = 'https://tamisha-incommunicative-enragedly.ngrok-free.dev/api/twilio/voice/reminder';

            Log::info('Initiating reminder call', [
                'target_number' => $targetNumber,
                'webhook_url' => $webhookUrl,
                'app_url' => config('app.url'),
            ]);

            // Validate that webhook URL is publicly accessible (not localhost)
            if (str_contains($webhookUrl, 'localhost') || str_contains($webhookUrl, '127.0.0.1')) {
                Log::warning('Webhook URL appears to be localhost - Twilio may not be able to reach it', [
                    'webhook_url' => $webhookUrl,
                ]);
            }

            $call = $this->twilioCallService->initiateOutboundCall(
                $targetNumber,
                $webhookUrl
            );

            return response()->json([
                'success' => true,
                'message' => 'Reminder call initiated successfully',
                'data' => [
                    'call_sid' => $call->sid,
                    'status' => $call->status,
                    'to' => $call->to,
                    'from' => $call->from,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initiate reminder call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate reminder call: ' . $e->getMessage(),
            ], 500);
        }
    }
}

