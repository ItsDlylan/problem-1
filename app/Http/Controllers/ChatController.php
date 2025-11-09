<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\AppointmentService;
use App\Services\ChatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final readonly class ChatController
{
    public function __construct(
        private ChatService $chatService,
        private AppointmentService $appointmentService,
    ) {
    }

    /**
     * Handle incoming chat message.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $patient = $request->user('patient');
        assert($patient instanceof Patient);

        $message = $request->input('message');

        // Get conversation history from cache (keyed by patient ID)
        $cacheKey = "chat_conversation_{$patient->id}";
        $conversationHistory = Cache::get($cacheKey, []);

        // Process message with OpenAI
        $result = $this->chatService->processMessage($message, $conversationHistory, $patient);

        // Update conversation history
        $conversationHistory[] = ['role' => 'user', 'content' => $message];
        $conversationHistory[] = ['role' => 'assistant', 'content' => $result['message']];

        // Store updated history (keep last 20 messages to avoid token limits)
        $conversationHistory = array_slice($conversationHistory, -20);
        Cache::put($cacheKey, $conversationHistory, now()->addHours(2));

        return response()->json([
            'message' => $result['message'],
            'extractedDetails' => $result['extractedDetails'],
        ]);
    }

    /**
     * Confirm and create appointment from extracted details.
     */
    public function confirmAppointment(Request $request): JsonResponse
    {
        $request->validate([
            'serviceOfferingId' => ['required', 'integer', 'exists:service_offerings,id'],
            'datetime' => ['required', 'string', 'date'],
        ]);

        $patient = $request->user('patient');
        assert($patient instanceof Patient);

        try {
            $appointment = $this->appointmentService->createFromChat(
                [
                    'serviceOfferingId' => $request->input('serviceOfferingId'),
                    'datetime' => $request->input('datetime'),
                ],
                $patient
            );

            // Clear conversation history after successful booking
            $cacheKey = "chat_conversation_{$patient->id}";
            Cache::forget($cacheKey);

            // Convert times to America/Chicago timezone for display
            $startAtChicago = Carbon::parse($appointment->start_at)->setTimezone('America/Chicago');
            $endAtChicago = Carbon::parse($appointment->end_at)->setTimezone('America/Chicago');

            return response()->json([
                'success' => true,
                'appointment' => [
                    'id' => $appointment->id,
                    'startAt' => $startAtChicago->toIso8601String(),
                    'endAt' => $endAtChicago->toIso8601String(),
                    'startAtFormatted' => $startAtChicago->format('l, F j, Y \a\t g:i A'),
                    'endAtFormatted' => $endAtChicago->format('l, F j, Y \a\t g:i A'),
                    'status' => $appointment->status,
                    'service' => [
                        'name' => $appointment->serviceOffering->service->name,
                        'description' => $appointment->serviceOffering->service->description,
                    ],
                    'doctor' => [
                        'name' => $appointment->doctor->display_name ?? trim("{$appointment->doctor->first_name} {$appointment->doctor->last_name}"),
                    ],
                    'facility' => [
                        'name' => $appointment->facility->name,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment: '.$e->getMessage(),
            ], 422);
        }
    }
}

