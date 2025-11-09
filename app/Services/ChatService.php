<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceOffering;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

final readonly class ChatService
{
    /**
     * Process a chat message and return OpenAI response with extracted appointment details.
     *
     * @param  array<string, array{role: string, content: string}>  $conversationHistory
     * @return array{message: string, extractedDetails: array{service?: string, datetime?: string, serviceOfferingId?: int, isAvailable?: bool, alternativeTimes?: array<int, array{startAt: string, endAt: string}>}|null}
     */
    public function processMessage(string $message, array $conversationHistory, Patient $patient): array
    {
        // Get available services for the system prompt
        $availableServices = $this->getAvailableServices();

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($availableServices);

        // Prepare messages for OpenAI
        // Include conversation history to maintain context across messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$conversationHistory,
            ['role' => 'user', 'content' => $message],
            ['role' => 'system', 'content' => 'IMPORTANT: You must respond with valid JSON in this exact format: {"response": "your conversational response", "service_name": "service if mentioned", "datetime": "ISO 8601 datetime if mentioned (in America/Chicago timezone)", "has_service": true/false, "has_datetime": true/false}. Always include the response field with your natural reply. Remember the conversation context and reference previous messages when relevant. All times should be in America/Chicago timezone.'],
        ];

        // Call OpenAI
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
        ]);

        $content = $response->choices[0]->message->content;

        // Try to extract JSON from the response
        $parsedContent = $this->extractJsonFromResponse($content);

        if (! is_array($parsedContent)) {
            // Fallback if JSON extraction fails
            $parsedContent = [
                'response' => $content,
                'has_service' => false,
                'has_datetime' => false,
            ];
        }

        $botMessage = $parsedContent['response'] ?? $content;
        $serviceName = $parsedContent['service_name'] ?? null;
        $datetime = $parsedContent['datetime'] ?? null;
        $hasService = $parsedContent['has_service'] ?? false;
        $hasDatetime = $parsedContent['has_datetime'] ?? false;

        // Extract appointment details - handle partial information
        $extractedDetails = null;
        
        // If we have a service, try to match it
        $serviceOffering = null;
        if ($hasService && $serviceName) {
            $availableServices = $this->getAvailableServices();
            $serviceOffering = $this->matchServiceToDatabase($serviceName, $availableServices);
        }
        
        // If we have both service and datetime, create full extracted details
        if ($hasService && $hasDatetime && $serviceName && $datetime && $serviceOffering) {
            if ($serviceOffering) {
                try {
                    // Parse datetime assuming America/Chicago timezone if no timezone is specified
                    // This is critical: if the AI extracts "2025-11-09T11:00:00" (no timezone),
                    // we want it to be 11:00 AM in America/Chicago, NOT 11:00 AM UTC converted to 5:00 AM CT
                    $parsedDatetime = Carbon::parse($datetime, 'America/Chicago');
                    
                    // Ensure the final datetime is in America/Chicago timezone
                    // If the datetime string had explicit timezone info, Carbon will convert it
                    // If it had no timezone info, it's already set to America/Chicago from above
                    $parsedDatetime = $parsedDatetime->setTimezone('America/Chicago');
                    
                    // Check availability for this doctor and time
                    $availabilityCheck = $this->checkDoctorAvailability(
                        $serviceOffering->doctor_id,
                        $serviceOffering->id,
                        $parsedDatetime
                    );

                    // If availability was checked, enhance the bot message with availability information
                    if (!$availabilityCheck['isAvailable']) {
                        $botMessage = $this->enhanceMessageWithAvailability(
                            $botMessage,
                            $parsedDatetime,
                            $availabilityCheck['alternatives'],
                            $conversationHistory
                        );
                    }

                    $extractedDetails = [
                        'service' => $serviceName,
                        'datetime' => $parsedDatetime->setTimezone('America/Chicago')->toIso8601String(),
                        'serviceOfferingId' => $serviceOffering->id,
                        'isAvailable' => $availabilityCheck['isAvailable'],
                        'serviceOffering' => [
                            'id' => $serviceOffering->id,
                            'service' => [
                                'id' => $serviceOffering->service->id,
                                'name' => $serviceOffering->service->name,
                                'description' => $serviceOffering->service->description,
                            ],
                            'doctor' => [
                                'id' => $serviceOffering->doctor->id,
                                'name' => $serviceOffering->doctor->display_name ?? trim("{$serviceOffering->doctor->first_name} {$serviceOffering->doctor->last_name}"),
                            ],
                            'facility' => [
                                'id' => $serviceOffering->facility->id,
                                'name' => $serviceOffering->facility->name,
                            ],
                        ],
                    ];

                    // Add alternative available times if requested time is not available
                    if (!$availabilityCheck['isAvailable'] && !empty($availabilityCheck['alternatives'])) {
                        $extractedDetails['alternativeTimes'] = $availabilityCheck['alternatives'];
                    }
                } catch (\Exception $e) {
                    // Invalid datetime, continue without extracted details
                }
            }
        } elseif ($hasService && $serviceName && $serviceOffering) {
            // Partial: We have service but not datetime
            $extractedDetails = [
                'service' => $serviceName,
                'serviceOfferingId' => $serviceOffering->id,
                'has_service' => true,
                'has_datetime' => false,
                'serviceOffering' => [
                    'id' => $serviceOffering->id,
                    'service' => [
                        'id' => $serviceOffering->service->id,
                        'name' => $serviceOffering->service->name,
                        'description' => $serviceOffering->service->description,
                    ],
                    'doctor' => [
                        'id' => $serviceOffering->doctor->id,
                        'name' => $serviceOffering->doctor->display_name ?? trim("{$serviceOffering->doctor->first_name} {$serviceOffering->doctor->last_name}"),
                    ],
                    'facility' => [
                        'id' => $serviceOffering->facility->id,
                        'name' => $serviceOffering->facility->name,
                    ],
                ],
            ];
        } elseif ($hasDatetime && $datetime) {
            // Partial: We have datetime but not service
            try {
                $parsedDatetime = Carbon::parse($datetime, 'America/Chicago')->setTimezone('America/Chicago');
                $extractedDetails = [
                    'datetime' => $parsedDatetime->toIso8601String(),
                    'has_service' => false,
                    'has_datetime' => true,
                ];
            } catch (\Exception $e) {
                // Invalid datetime, continue without extracted details
            }
        }

        return [
            'message' => $botMessage,
            'extractedDetails' => $extractedDetails,
        ];
    }

    /**
     * Get available services from the database.
     *
     * @return Collection<int, ServiceOffering>
     */
    private function getAvailableServices(): Collection
    {
        return ServiceOffering::with(['service', 'doctor', 'facility'])
            ->where('active', true)
            ->where('visibility', 'public')
            ->get();
    }

    /**
     * Build the system prompt with available services.
     */
    private function buildSystemPrompt(Collection $services): string
    {
        // Get current date and time in natural language format (America/Chicago timezone)
        $now = Carbon::now('America/Chicago');
        $currentDate = $now->format('l, F j, Y'); // e.g., "Monday, January 15, 2024"
        $currentTime = $now->format('g:i A'); // e.g., "2:30 PM"
        $currentDateTime = "Today is {$currentDate}, {$currentTime}";

        $servicesList = $services->map(function ($offering) {
            $service = $offering->service;
            $doctor = $offering->doctor;
            $facility = $offering->facility;

            return sprintf(
                '- %s (at %s with %s)',
                $service->name,
                $facility->name,
                $doctor->display_name ?? trim("{$doctor->first_name} {$doctor->last_name}")
            );
        })->join("\n");

        return <<<PROMPT
You are a friendly and helpful scheduling assistant for a healthcare facility. Your role is to help patients schedule appointments through natural conversation.

{$currentDateTime} (America/Chicago timezone)

IMPORTANT TIMEZONE INFORMATION: All times are in America/Chicago timezone (Central Time). When users mention times, they are referring to Central Time. Always interpret and work with times in America/Chicago timezone.

Your goals:
1. Greet the user warmly and ask how you can help them schedule an appointment
2. Ask what service they need
3. Ask for their preferred date and time (in Central Time / America/Chicago timezone)
4. Once you have both the service and datetime, check if the doctor has availability for that time
5. If the requested time is not available, suggest alternative times or ask the user for a different preference
6. Remember the context of the conversation and refer back to previous messages when appropriate

Available services:
{$servicesList}

When the user mentions a service, try to match it to one of the available services above. When they mention a date/time, extract it and convert it to ISO 8601 format (YYYY-MM-DDTHH:mm:ss) in America/Chicago timezone. CRITICAL: Extract the EXACT time the user mentions without any timezone conversion. If the user says "11 AM", extract "2025-11-09T11:00:00" (or the appropriate date) - the time "11:00" should remain "11:00", not be converted. The user's time is already in Central Time.

IMPORTANT: Always check availability before confirming an appointment time. If availability information is provided indicating the requested time is not available, inform the user politely and suggest alternatives or ask for a different time preference. All times you mention should be in Central Time (America/Chicago).

Be conversational, friendly, and helpful. Don't be too formal. Remember the context of your conversation with the user and reference previous messages when relevant. If the user hasn't provided both service and datetime yet, continue the conversation naturally to gather that information.
PROMPT;
    }

    /**
     * Match a service name/description to a database service offering.
     */
    private function matchServiceToDatabase(string $serviceDescription, Collection $availableServices): ?ServiceOffering
    {
        // Simple fuzzy matching - find the service with the best match
        $bestMatch = null;
        $bestScore = 0;

        foreach ($availableServices as $offering) {
            $service = $offering->service;
            $serviceName = strtolower($service->name);
            $serviceDesc = strtolower($service->description ?? '');
            $searchTerm = strtolower($serviceDescription);

            // Check if search term contains service name or vice versa
            $nameScore = 0;
            if (str_contains($searchTerm, $serviceName) || str_contains($serviceName, $searchTerm)) {
                $nameScore = min(strlen($serviceName), strlen($searchTerm)) / max(strlen($serviceName), strlen($searchTerm));
            }

            // Check description match
            $descScore = 0;
            if ($serviceDesc && (str_contains($searchTerm, $serviceDesc) || str_contains($serviceDesc, $searchTerm))) {
                $descScore = min(strlen($serviceDesc), strlen($searchTerm)) / max(strlen($serviceDesc), strlen($searchTerm));
            }

            $score = max($nameScore, $descScore * 0.7); // Prefer name matches

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $offering;
            }
        }

        // Only return if we have a reasonable match (at least 30% similarity)
        return $bestScore >= 0.3 ? $bestMatch : null;
    }

    /**
     * Enhance bot message with availability information when time is not available.
     *
     * @param  string  $initialMessage
     * @param  Carbon  $requestedDatetime
     * @param  array<int, array{startAt: string, endAt: string}>  $alternatives
     * @param  array<string, array{role: string, content: string}>  $conversationHistory
     * @return string
     */
    private function enhanceMessageWithAvailability(
        string $initialMessage,
        Carbon $requestedDatetime,
        array $alternatives,
        array $conversationHistory
    ): string {
        // Format alternative times for the prompt (convert to America/Chicago timezone)
        $alternativeTimesText = '';
        if (!empty($alternatives)) {
            $formattedAlternatives = array_map(function ($alt) {
                $start = Carbon::parse($alt['startAt'])->setTimezone('America/Chicago');
                return $start->format('l, F j, Y \a\t g:i A');
            }, array_slice($alternatives, 0, 3)); // Limit to 3 alternatives for the prompt
            
            $alternativeTimesText = "\n\nAvailable alternative times:\n" . implode("\n", array_map(fn($t) => "- {$t}", $formattedAlternatives));
        }

        $requestedTimeFormatted = $requestedDatetime->setTimezone('America/Chicago')->format('l, F j, Y \a\t g:i A');

        // Make a follow-up call to OpenAI to enhance the response with availability info
        $availabilityPrompt = <<<PROMPT
The user requested an appointment for {$requestedTimeFormatted}, but this time is not available.{$alternativeTimesText}

Please update your response to:
1. Politely inform the user that the requested time is not available
2. Suggest the alternative times provided above, or ask if they'd like to choose a different time
3. Keep your response conversational and helpful

Your previous response was: "{$initialMessage}"

Provide an updated response that incorporates the availability information.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful scheduling assistant. Update your response based on availability information provided.'],
                    ...$conversationHistory,
                    ['role' => 'assistant', 'content' => $initialMessage],
                    ['role' => 'user', 'content' => $availabilityPrompt],
                ],
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? $initialMessage;
        } catch (\Exception $e) {
            // If enhancement fails, return original message
            return $initialMessage;
        }
    }

    /**
     * Check doctor availability for a specific datetime.
     *
     * @param  int  $doctorId
     * @param  int  $serviceOfferingId
     * @param  Carbon  $datetime
     * @return array{isAvailable: bool, alternatives: array<int, array{startAt: string, endAt: string}>}
     */
    private function checkDoctorAvailability(int $doctorId, int $serviceOfferingId, Carbon $datetime): array
    {
        // Ensure datetime is in America/Chicago timezone for comparison
        $datetime = $datetime->setTimezone('America/Chicago');
        
        // Check for available slots within ±30 minutes of the requested time
        $timeWindowStart = $datetime->copy()->subMinutes(30);
        $timeWindowEnd = $datetime->copy()->addMinutes(30);

        $availableSlots = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('service_offering_id', $serviceOfferingId)
            ->where('status', 'open')
            ->whereBetween('start_at', [$timeWindowStart, $timeWindowEnd])
            ->orderBy('start_at', 'asc')
            ->get();

        // Check if the exact requested time (or very close) is available
        $isAvailable = $availableSlots->contains(function ($slot) use ($datetime) {
            // Convert slot times to America/Chicago for comparison
            $slotStart = Carbon::parse($slot->start_at)->setTimezone('America/Chicago');
            $slotEnd = Carbon::parse($slot->end_at)->setTimezone('America/Chicago');
            // Check if the slot overlaps with the requested time
            return $slotStart <= $datetime && $slotEnd >= $datetime;
        });

        // If not available, get alternative slots for the same day or next few days
        $alternatives = [];
        if (!$isAvailable) {
            // Look for available slots on the same day, then next 7 days
            $searchStart = $datetime->copy()->startOfDay();
            $searchEnd = $datetime->copy()->addDays(7)->endOfDay();

            $alternativeSlots = AvailabilitySlot::where('doctor_id', $doctorId)
                ->where('service_offering_id', $serviceOfferingId)
                ->where('status', 'open')
                ->where('start_at', '>=', $searchStart)
                ->where('start_at', '<=', $searchEnd)
                ->orderBy('start_at', 'asc')
                ->limit(5) // Limit to 5 alternatives
                ->get();

            foreach ($alternativeSlots as $slot) {
                // Convert to America/Chicago timezone before returning
                $startAtChicago = Carbon::parse($slot->start_at)->setTimezone('America/Chicago');
                $endAtChicago = Carbon::parse($slot->end_at)->setTimezone('America/Chicago');
                $alternatives[] = [
                    'startAt' => $startAtChicago->toIso8601String(),
                    'endAt' => $endAtChicago->toIso8601String(),
                ];
            }
        }

        return [
            'isAvailable' => $isAvailable,
            'alternatives' => $alternatives,
        ];
    }

    /**
     * Extract JSON from OpenAI response, handling cases where it's wrapped in markdown.
     */
    private function extractJsonFromResponse(string $content): ?array
    {
        // Try to find JSON in the response (might be wrapped in ```json or just plain JSON)
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $content, $matches)) {
            $json = $matches[1];
        } else {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}

