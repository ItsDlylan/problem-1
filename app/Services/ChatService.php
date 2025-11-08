<?php

declare(strict_types=1);

namespace App\Services;

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
     * @return array{message: string, extractedDetails: array{service?: string, datetime?: string, serviceOfferingId?: int}|null}
     */
    public function processMessage(string $message, array $conversationHistory, Patient $patient): array
    {
        // Get available services for the system prompt
        $availableServices = $this->getAvailableServices();

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($availableServices);

        // Prepare messages for OpenAI
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$conversationHistory,
            ['role' => 'user', 'content' => $message],
            ['role' => 'system', 'content' => 'IMPORTANT: You must respond with valid JSON in this exact format: {"response": "your conversational response", "service_name": "service if mentioned", "datetime": "ISO 8601 datetime if mentioned", "has_service": true/false, "has_datetime": true/false}. Always include the response field with your natural reply.'],
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

        // Extract appointment details if both service and datetime are provided
        $extractedDetails = null;
        if ($hasService && $hasDatetime && $serviceName && $datetime) {
            $serviceOffering = $this->matchServiceToDatabase($serviceName, $availableServices);

            if ($serviceOffering) {
                try {
                    $parsedDatetime = Carbon::parse($datetime);
                    $extractedDetails = [
                        'service' => $serviceName,
                        'datetime' => $parsedDatetime->toIso8601String(),
                        'serviceOfferingId' => $serviceOffering->id,
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
                } catch (\Exception $e) {
                    // Invalid datetime, continue without extracted details
                }
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

Your goals:
1. Greet the user warmly and ask how you can help them schedule an appointment
2. Ask what service they need
3. Ask for their preferred date and time
4. Once you have both the service and datetime, acknowledge them and let them know you'll help confirm the appointment

Available services:
{$servicesList}

When the user mentions a service, try to match it to one of the available services above. When they mention a date/time, extract it and convert it to ISO 8601 format (YYYY-MM-DDTHH:mm:ss).

Be conversational, friendly, and helpful. Don't be too formal. If the user hasn't provided both service and datetime yet, continue the conversation naturally to gather that information.
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

