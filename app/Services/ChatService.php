<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceOffering;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
            ['role' => 'system', 'content' => 'IMPORTANT: You must respond with valid JSON in this exact format: {"response": "your conversational response", "service_name": "service if mentioned", "doctor_name": "doctor name if mentioned (e.g., Dr. John Smith, John Smith, or just Smith)", "datetime": "ISO 8601 datetime if mentioned (in America/Chicago timezone)", "has_service": true/false, "has_doctor": true/false, "has_datetime": true/false}. Always include the response field with your natural reply. Extract doctor names from phrases like "with Doctor Name", "with Dr. Name", "see Doctor Name", etc. Remember the conversation context and reference previous messages when relevant. All times should be in America/Chicago timezone.'],
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
                'has_doctor' => false,
                'has_datetime' => false,
            ];
        }

        $botMessage = $parsedContent['response'] ?? $content;
        $serviceName = $parsedContent['service_name'] ?? null;
        $doctorName = $parsedContent['doctor_name'] ?? null;
        $datetime = $parsedContent['datetime'] ?? null;
        $hasService = $parsedContent['has_service'] ?? false;
        $hasDoctor = $parsedContent['has_doctor'] ?? false;
        $hasDatetime = $parsedContent['has_datetime'] ?? false;

        // Log extracted information for debugging
        Log::info('ChatService: Extracted information from message', [
            'service_name' => $serviceName,
            'doctor_name' => $doctorName,
            'datetime' => $datetime,
            'has_service' => $hasService,
            'has_doctor' => $hasDoctor,
            'has_datetime' => $hasDatetime,
            'parsed_content' => $parsedContent,
        ]);

        // Extract appointment details - handle partial information
        $extractedDetails = null;
        
        // If we have a service, try to match it (with optional doctor name)
        $serviceOffering = null;
        if ($hasService && $serviceName) {
            $availableServices = $this->getAvailableServices();
            
            // If doctor name is provided, also search all doctors (not just those with active public offerings)
            // This handles cases where a doctor exists but doesn't have active public service offerings yet
            if ($hasDoctor && $doctorName) {
                // First try with available services (preferred - active public offerings)
                $serviceOffering = $this->matchServiceToDatabase(
                    $serviceName,
                    $availableServices,
                    $doctorName
                );
                
                // If no match found, search all service offerings (including non-public)
                // This helps find doctors that exist but may not have active public offerings
                if (!$serviceOffering) {
                    Log::info('ChatService: No match in active public offerings, searching all active service offerings', [
                        'doctor_name' => $doctorName,
                        'service_name' => $serviceName,
                    ]);
                    
                    $allServices = ServiceOffering::with(['service', 'doctor', 'facility'])
                        ->where('active', true) // Still require active, but allow non-public
                        ->get();
                    
                    $serviceOffering = $this->matchServiceToDatabase(
                        $serviceName,
                        $allServices,
                        $doctorName
                    );
                    
                    if ($serviceOffering) {
                        Log::info('ChatService: Found match in all active service offerings (non-public)', [
                            'doctor_name' => $doctorName,
                            'matched_doctor' => $serviceOffering->doctor->display_name,
                            'service_offering_id' => $serviceOffering->id,
                        ]);
                    } else {
                        // Last resort: search all doctors directly to see if doctor exists
                        Log::info('ChatService: No match in service offerings, checking if doctor exists in database', [
                            'doctor_name' => $doctorName,
                        ]);
                        
                        $allDoctors = \App\Models\Doctor::whereNull('deleted_at')->get();
                        $matchedDoctor = null;
                        
                        foreach ($allDoctors as $doctor) {
                            $doctorDisplayName = strtolower($doctor->display_name ?? '');
                            $doctorFullName = strtolower(trim("{$doctor->first_name} {$doctor->last_name}"));
                            $doctorLastName = strtolower($doctor->last_name ?? '');
                            $doctorNameLower = strtolower(trim($doctorName));
                            $doctorNameCleaned = preg_replace('/^(dr\.?|doctor)\s+/i', '', $doctorNameLower);
                            
                            // Check if this doctor matches
                            if ($doctorDisplayName && str_contains($doctorDisplayName, $doctorNameCleaned)) {
                                $matchedDoctor = $doctor;
                                break;
                            } elseif ($doctorLastName && str_contains($doctorNameCleaned, $doctorLastName)) {
                                $matchedDoctor = $doctor;
                                break;
                            }
                        }
                        
                        if ($matchedDoctor) {
                            // Check what service offerings this doctor has
                            $doctorServiceOfferings = ServiceOffering::where('doctor_id', $matchedDoctor->id)
                                ->with(['service', 'doctor', 'facility'])
                                ->get();
                            
                            $serviceOfferingsDetails = $doctorServiceOfferings->map(function ($offering) {
                                return [
                                    'id' => $offering->id,
                                    'service_id' => $offering->service_id,
                                    'service_name' => $offering->service->name,
                                    'active' => $offering->active,
                                    'visibility' => $offering->visibility,
                                ];
                            })->toArray();
                            
                            Log::info('ChatService: Doctor exists in database but has no matching service offerings', [
                                'doctor_name' => $doctorName,
                                'found_doctor_id' => $matchedDoctor->id,
                                'found_doctor_display_name' => $matchedDoctor->display_name,
                                'found_doctor_first_name' => $matchedDoctor->first_name,
                                'found_doctor_last_name' => $matchedDoctor->last_name,
                                'service_name' => $serviceName,
                                'doctor_service_offerings' => $serviceOfferingsDetails,
                                'total_offerings' => $doctorServiceOfferings->count(),
                            ]);
                        } else {
                            Log::warning('ChatService: Doctor not found in database at all', [
                                'doctor_name' => $doctorName,
                                'searched_doctors_count' => $allDoctors->count(),
                            ]);
                        }
                    }
                }
                
                // Log available doctors for debugging
                $availableDoctors = $availableServices->map(function ($offering) {
                    $doctor = $offering->doctor;
                    return [
                        'id' => $doctor->id,
                        'display_name' => $doctor->display_name,
                        'first_name' => $doctor->first_name,
                        'last_name' => $doctor->last_name,
                        'service' => $offering->service->name,
                    ];
                })->unique('id')->values()->toArray();
                
                Log::info('ChatService: Available doctors in system', [
                    'searching_for_doctor' => $doctorName,
                    'available_doctors' => $availableDoctors,
                ]);
            } else {
                // No doctor name provided, use normal matching
                $serviceOffering = $this->matchServiceToDatabase(
                    $serviceName,
                    $availableServices,
                    null
                );
            }
            
            Log::info('ChatService: Matching result', [
                'service_name' => $serviceName,
                'doctor_name' => $doctorName,
                'matched_service_offering_id' => $serviceOffering?->id,
                'matched_doctor_id' => $serviceOffering?->doctor?->id ?? null,
                'matched_doctor_name' => $serviceOffering?->doctor?->display_name ?? null,
            ]);
            
            // If doctor name was provided but no match found, enhance the bot message
            if ($hasDoctor && $doctorName && !$serviceOffering) {
                // If doctor name was provided but no match found, enhance the bot message
                // Try to match without doctor name to see if service exists
                $serviceOfferingWithoutDoctor = $this->matchServiceToDatabase(
                    $serviceName,
                    $availableServices,
                    null
                );
                
                if ($serviceOfferingWithoutDoctor) {
                    // Service exists but doctor doesn't match - inform user
                    $botMessage = $this->enhanceMessageWithDoctorNotFound(
                        $botMessage,
                        $doctorName,
                        $serviceName,
                        $conversationHistory
                    );
                }
            }
        }
        
        // Check if bot message indicates failure - but allow if we have alternatives to show
        // We'll check this again after availability check to see if alternatives were found
        $botMessageIndicatesFailure = stripos($botMessage, "isn't available") !== false || 
                                       stripos($botMessage, "not available") !== false ||
                                       stripos($botMessage, "can't find") !== false ||
                                       stripos($botMessage, "unfortunately") !== false ||
                                       stripos($botMessage, "sorry") !== false;
        
        // Check if bot message is asking for more information (date/time) - don't show confirmation card yet
        $botMessageAskingForInfo = stripos($botMessage, "please let me know") !== false ||
                                    stripos($botMessage, "could you please") !== false ||
                                    stripos($botMessage, "when would you like") !== false ||
                                    stripos($botMessage, "what date") !== false ||
                                    stripos($botMessage, "what time") !== false ||
                                    stripos($botMessage, "specific date") !== false ||
                                    stripos($botMessage, "preferred date") !== false;
        
        // Only set extractedDetails if we have a valid match AND the bot is not asking for more information
        // Note: We allow failure messages if we have alternatives (handled inside the block)
        // If we have both service and datetime, create full extracted details
        if ($hasService && $hasDatetime && $serviceName && $datetime && $serviceOffering && !$botMessageAskingForInfo) {
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
                        
                        // If we have alternatives, include them in extractedDetails so frontend can display them
                        // But don't show confirmation card for unavailable time - user needs to pick alternative
                        if (!empty($availabilityCheck['alternatives'])) {
                            // Include alternatives in extractedDetails but mark as not available
                            // Frontend can display alternatives but won't show confirm button
                            $extractedDetails = [
                                'service' => $serviceName,
                                'datetime' => $parsedDatetime->setTimezone('America/Chicago')->toIso8601String(),
                                'serviceOfferingId' => $serviceOffering->id,
                                'isAvailable' => false,
                                'alternativeTimes' => $availabilityCheck['alternatives'],
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
                        } else {
                            // No alternatives, don't show confirmation card
                            $extractedDetails = null;
                        }
                    } else {
                        // Time is available, set extractedDetails
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
                    }
                    
                    // After availability check, update bot message if needed (but preserve alternatives)
                    if ($hasDoctor && $doctorName && $serviceOffering) {
                        // Check if message contains alternative times (don't overwrite it)
                        $messageHasAlternatives = stripos($botMessage, "alternative") !== false ||
                                                  stripos($botMessage, "available times") !== false ||
                                                  stripos($botMessage, "other times") !== false ||
                                                  stripos($botMessage, "consider one of") !== false;
                        
                        // Only update if the bot message suggests failure AND it doesn't already have alternatives
                        // AND the time is available (if unavailable, keep the availability message with alternatives)
                        if (!$messageHasAlternatives && 
                            $availabilityCheck['isAvailable'] &&
                            (stripos($botMessage, "isn't available") !== false || 
                             stripos($botMessage, "not available") !== false ||
                             stripos($botMessage, "can't find") !== false)) {
                            $matchedDoctorName = $serviceOffering->doctor->display_name ?? trim("{$serviceOffering->doctor->first_name} {$serviceOffering->doctor->last_name}");
                            $matchedServiceName = $serviceOffering->service->name;
                            
                            Log::info('ChatService: Updating bot message to reflect successful match', [
                                'original_message' => $botMessage,
                                'matched_doctor' => $matchedDoctorName,
                                'matched_service' => $matchedServiceName,
                            ]);
                            
                            // Generate a positive message
                            $botMessage = "Great! I found {$matchedServiceName} with {$matchedDoctorName}. The requested time is available. Would you like to confirm this appointment?";
                        }
                    }
                } catch (\Exception $e) {
                    // Invalid datetime, continue without extracted details
                    $extractedDetails = null;
                }
            }
        } elseif ($hasService && $serviceName && $serviceOffering && !$botMessageIndicatesFailure && !$botMessageAskingForInfo) {
            // Partial: We have service but not datetime
            // Only show confirmation card if bot message is positive AND not asking for more info
            // Don't show confirmation card if bot is asking for date/time
            $extractedDetails = null;
        } elseif ($hasDatetime && $datetime && !$botMessageIndicatesFailure && !$botMessageAskingForInfo) {
            // Partial: We have datetime but not service
            // Don't show confirmation card if we don't have a service match
            $extractedDetails = null;
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

        Log::info('ChatService: What are the available services?', [
            'services_list' => $servicesList,
        ]);

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

When the user mentions a service, try to match it to one of the available services above. When they mention a doctor name (e.g., "with Dr. John Smith", "see Doctor Name", "schedule with Smith"), extract the doctor name. When they mention a date/time, extract it and convert it to ISO 8601 format (YYYY-MM-DDTHH:mm:ss) in America/Chicago timezone. CRITICAL: Extract the EXACT time the user mentions without any timezone conversion. If the user says "11 AM", extract "2025-11-09T11:00:00" (or the appropriate date) - the time "11:00" should remain "11:00", not be converted. The user's time is already in Central Time.

IMPORTANT: Always check availability before confirming an appointment time. If availability information is provided indicating the requested time is not available, inform the user politely and suggest alternatives or ask for a different time preference. All times you mention should be in Central Time (America/Chicago).

Be conversational, friendly, and helpful. Don't be too formal. Remember the context of your conversation with the user and reference previous messages when relevant. If the user hasn't provided both service and datetime yet, continue the conversation naturally to gather that information.
PROMPT;
    }

    /**
     * Match a service name/description to a database service offering.
     * Optionally match by doctor name as well.
     *
     * @param  string  $serviceDescription
     * @param  Collection<int, ServiceOffering>  $availableServices
     * @param  string|null  $doctorName
     * @return ServiceOffering|null
     */
    private function matchServiceToDatabase(string $serviceDescription, Collection $availableServices, ?string $doctorName = null): ?ServiceOffering
    {
        // Log matching attempt
        Log::info('ChatService: Starting service/doctor matching', [
            'service_description' => $serviceDescription,
            'doctor_name' => $doctorName,
            'total_offerings' => $availableServices->count(),
        ]);

        // Simple fuzzy matching - find the service with the best match
        $bestMatch = null;
        $bestScore = 0;
        $matchDetails = [];

        foreach ($availableServices as $offering) {
            $service = $offering->service;
            $doctor = $offering->doctor;
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

            $serviceScore = max($nameScore, $descScore * 0.7); // Prefer name matches

            // If doctor name is provided, also match by doctor
            $doctorScore = 0;
            if ($doctorName !== null) {
                $doctorNameLower = strtolower(trim($doctorName));
                
                // Get doctor's display name or full name
                $doctorDisplayName = strtolower($doctor->display_name ?? '');
                $doctorFullName = strtolower(trim("{$doctor->first_name} {$doctor->last_name}"));
                $doctorLastName = strtolower($doctor->last_name ?? '');
                $doctorFirstName = strtolower($doctor->first_name ?? '');
                
                // Remove common prefixes from user input for better matching
                $doctorNameCleaned = preg_replace('/^(dr\.?|doctor)\s+/i', '', $doctorNameLower);
                
                // Log doctor matching details for this specific doctor
                Log::debug('ChatService: Doctor name matching details', [
                    'offering_id' => $offering->id,
                    'doctor_id' => $doctor->id,
                    'user_input_original' => $doctorName,
                    'user_input_lower' => $doctorNameLower,
                    'user_input_cleaned' => $doctorNameCleaned,
                    'doctor_display_name' => $doctor->display_name,
                    'doctor_display_name_lower' => $doctorDisplayName,
                    'doctor_full_name' => trim("{$doctor->first_name} {$doctor->last_name}"),
                    'doctor_full_name_lower' => $doctorFullName,
                    'doctor_last_name' => $doctor->last_name,
                    'doctor_last_name_lower' => $doctorLastName,
                    'doctor_first_name' => $doctor->first_name,
                    'doctor_first_name_lower' => $doctorFirstName,
                ]);
                
                // Check exact match with display name
                if ($doctorDisplayName && $doctorNameLower === $doctorDisplayName) {
                    $doctorScore = 1.0;
                }
                // Check exact match with cleaned name vs display name
                elseif ($doctorDisplayName && $doctorNameCleaned === $doctorDisplayName) {
                    $doctorScore = 1.0;
                }
                // Check exact match with full name
                elseif ($doctorNameLower === $doctorFullName) {
                    $doctorScore = 1.0;
                }
                // Check exact match with cleaned name vs full name
                elseif ($doctorNameCleaned === $doctorFullName) {
                    $doctorScore = 1.0;
                }
                // Check if display name contains user input or vice versa (bidirectional)
                if ($doctorDisplayName && (str_contains($doctorDisplayName, $doctorNameLower) || str_contains($doctorNameLower, $doctorDisplayName))) {
                    $doctorScore = max($doctorScore, min(strlen($doctorDisplayName), strlen($doctorNameLower)) / max(strlen($doctorDisplayName), strlen($doctorNameLower)));
                }
                // Check if display name contains cleaned input (e.g., "Dr. John Pollich" contains "Pollich")
                // This is important when user says "Dr. Pollich" or just "Pollich"
                // Remove the condition check - always check if display name contains cleaned input
                if ($doctorDisplayName && str_contains($doctorDisplayName, $doctorNameCleaned)) {
                    $displayNameScore = min(strlen($doctorDisplayName), strlen($doctorNameCleaned)) / max(strlen($doctorDisplayName), strlen($doctorNameCleaned));
                    $doctorScore = max($doctorScore, $displayNameScore);
                    Log::debug('ChatService: Display name contains cleaned input match', [
                        'doctor_id' => $doctor->id,
                        'doctor_display_name' => $doctor->display_name,
                        'user_input_cleaned' => $doctorNameCleaned,
                        'display_name_score' => $displayNameScore,
                        'doctor_score_after' => $doctorScore,
                    ]);
                }
                // Check if full name contains user input or vice versa (bidirectional)
                if (str_contains($doctorFullName, $doctorNameLower) || str_contains($doctorNameLower, $doctorFullName)) {
                    $doctorScore = max($doctorScore, min(strlen($doctorFullName), strlen($doctorNameLower)) / max(strlen($doctorFullName), strlen($doctorNameLower)));
                }
                // Check if full name contains cleaned input
                if (str_contains($doctorFullName, $doctorNameCleaned) && $doctorNameCleaned !== $doctorNameLower) {
                    $doctorScore = max($doctorScore, min(strlen($doctorFullName), strlen($doctorNameCleaned)) / max(strlen($doctorFullName), strlen($doctorNameCleaned)));
                }
                
                // Always check last name match independently (e.g., "Pollich" or "Dr. Pollich" matches "Dr. John Pollich")
                // This ensures we catch cases where user says just the last name
                // Check both directions: user input contains last name OR last name contains user input
                $lastNameMatchLower = $doctorLastName && (str_contains($doctorNameLower, $doctorLastName) || str_contains($doctorLastName, $doctorNameLower));
                $lastNameMatchCleaned = $doctorLastName && (str_contains($doctorNameCleaned, $doctorLastName) || str_contains($doctorLastName, $doctorNameCleaned));
                if ($lastNameMatchLower || $lastNameMatchCleaned) {
                    $doctorScore = max($doctorScore, 0.9); // High score for last name match
                    Log::debug('ChatService: Last name match found', [
                        'doctor_id' => $doctor->id,
                        'doctor_display_name' => $doctor->display_name,
                        'doctor_last_name' => $doctor->last_name,
                        'user_input_lower' => $doctorNameLower,
                        'user_input_cleaned' => $doctorNameCleaned,
                        'match_via_lower' => $lastNameMatchLower,
                        'match_via_cleaned' => $lastNameMatchCleaned,
                        'doctor_score_after' => $doctorScore,
                    ]);
                } else {
                    // Log when last name doesn't match for debugging
                    if ($doctorLastName) {
                        Log::debug('ChatService: Last name NO match', [
                            'doctor_id' => $doctor->id,
                            'doctor_display_name' => $doctor->display_name,
                            'doctor_last_name' => $doctor->last_name,
                            'user_input_lower' => $doctorNameLower,
                            'user_input_cleaned' => $doctorNameCleaned,
                            'check_lower' => str_contains($doctorNameLower, $doctorLastName) || str_contains($doctorLastName, $doctorNameLower),
                            'check_cleaned' => str_contains($doctorNameCleaned, $doctorLastName) || str_contains($doctorLastName, $doctorNameCleaned),
                        ]);
                    }
                }
                
                // Always check first name match independently
                $firstNameMatchLower = $doctorFirstName && str_contains($doctorNameLower, $doctorFirstName);
                $firstNameMatchCleaned = $doctorFirstName && str_contains($doctorNameCleaned, $doctorFirstName);
                if ($firstNameMatchLower || $firstNameMatchCleaned) {
                    $doctorScore = max($doctorScore, 0.6); // Partial match for first name
                    Log::debug('ChatService: First name match found', [
                        'doctor_id' => $doctor->id,
                        'doctor_first_name' => $doctor->first_name,
                        'user_input_lower' => $doctorNameLower,
                        'user_input_cleaned' => $doctorNameCleaned,
                        'match_via_lower' => $firstNameMatchLower,
                        'match_via_cleaned' => $firstNameMatchCleaned,
                        'doctor_score_after' => $doctorScore,
                    ]);
                }
                
                // Log final doctor score for this doctor
                Log::debug('ChatService: Final doctor score for this doctor', [
                    'doctor_id' => $doctor->id,
                    'doctor_display_name' => $doctor->display_name,
                    'doctor_score' => $doctorScore,
                ]);
            }

            // Calculate combined score
            // If doctor name is provided, prioritize matches that have both service and doctor match
            if ($doctorName !== null) {
                // If both match, boost the score significantly
                if ($serviceScore > 0 && $doctorScore > 0) {
                    $score = ($serviceScore * 0.5) + ($doctorScore * 0.5);
                    // Boost score for exact matches
                    if ($serviceScore >= 0.8 && $doctorScore >= 0.7) {
                        $score = min(1.0, $score * 1.3);
                    }
                } elseif ($doctorScore > 0) {
                    // Doctor matches but service doesn't - lower score
                    $score = $doctorScore * 0.3;
                } else {
                    // Service matches but doctor doesn't - lower score
                    $score = $serviceScore * 0.3;
                }
            } else {
                // No doctor name provided, use service score only
                $score = $serviceScore;
            }

            // Store match details for logging
            $matchDetails[] = [
                'offering_id' => $offering->id,
                'service_name' => $offering->service->name,
                'doctor_id' => $offering->doctor->id,
                'doctor_display_name' => $offering->doctor->display_name,
                'doctor_first_name' => $offering->doctor->first_name,
                'doctor_last_name' => $offering->doctor->last_name,
                'service_score' => $serviceScore,
                'doctor_score' => $doctorScore,
                'combined_score' => $score,
            ];

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $offering;
            }
        }

        // Log all match attempts for debugging
        Log::info('ChatService: Matching details for all offerings', [
            'service_description' => $serviceDescription,
            'doctor_name' => $doctorName,
            'match_details' => $matchDetails,
            'best_score' => $bestScore,
            'best_match_offering_id' => $bestMatch?->id,
            'best_match_doctor' => $bestMatch ? [
                'id' => $bestMatch->doctor->id,
                'display_name' => $bestMatch->doctor->display_name,
                'first_name' => $bestMatch->doctor->first_name,
                'last_name' => $bestMatch->doctor->last_name,
            ] : null,
        ]);

        // Only return if we have a reasonable match
        // If doctor name was provided, require BOTH service AND doctor match
        if ($doctorName !== null) {
            // Find the best match that actually has a doctor score > 0
            $bestMatchWithDoctor = null;
            $bestScoreWithDoctor = 0;
            
            foreach ($matchDetails as $detail) {
                if ($detail['doctor_score'] > 0 && $detail['combined_score'] > $bestScoreWithDoctor) {
                    $bestScoreWithDoctor = $detail['combined_score'];
                    // Find the actual offering
                    $bestMatchWithDoctor = $availableServices->firstWhere('id', $detail['offering_id']);
                }
            }
            
            // If we found a match with doctor score > 0, use that
            if ($bestMatchWithDoctor && $bestScoreWithDoctor >= 0.2) {
                Log::info('ChatService: Matching result (with doctor name - found doctor match)', [
                    'doctor_name' => $doctorName,
                    'best_score' => $bestScoreWithDoctor,
                    'threshold' => 0.2,
                    'passed' => true,
                    'matched_doctor' => [
                        'id' => $bestMatchWithDoctor->doctor->id,
                        'display_name' => $bestMatchWithDoctor->doctor->display_name,
                    ],
                ]);
                return $bestMatchWithDoctor;
            }
            
            // No doctor match found - return null
            Log::info('ChatService: Matching result (with doctor name - NO doctor match found)', [
                'doctor_name' => $doctorName,
                'best_score' => $bestScore,
                'best_score_with_doctor' => $bestScoreWithDoctor,
                'threshold' => 0.2,
                'passed' => false,
                'matched_doctor' => null,
                'note' => 'Doctor name provided but no matching doctor found in service offerings',
            ]);
            return null;
        }
        
        // Only return if we have a reasonable match (at least 30% similarity)
        $result = $bestScore >= 0.3 ? $bestMatch : null;
        Log::info('ChatService: Matching result (service only)', [
            'service_description' => $serviceDescription,
            'best_score' => $bestScore,
            'threshold' => 0.3,
            'passed' => $bestScore >= 0.3,
            'matched_service' => $result ? $result->service->name : null,
        ]);
        return $result;
    }

    /**
     * Enhance bot message when doctor name is not found for the requested service.
     *
     * @param  string  $initialMessage
     * @param  string  $doctorName
     * @param  string  $serviceName
     * @param  array<string, array{role: string, content: string}>  $conversationHistory
     * @return string
     */
    private function enhanceMessageWithDoctorNotFound(
        string $initialMessage,
        string $doctorName,
        string $serviceName,
        array $conversationHistory
    ): string {
        // Get available doctors for this service to suggest alternatives
        $availableServices = $this->getAvailableServices();
        $serviceOfferings = $availableServices->filter(function ($offering) use ($serviceName) {
            $service = $offering->service;
            $serviceNameLower = strtolower($service->name);
            $searchTerm = strtolower($serviceName);
            return str_contains($serviceNameLower, $searchTerm) || str_contains($searchTerm, $serviceNameLower);
        });

        $availableDoctors = $serviceOfferings->map(function ($offering) {
            $doctor = $offering->doctor;
            return $doctor->display_name ?? trim("{$doctor->first_name} {$doctor->last_name}");
        })->unique()->values()->toArray();

        $doctorsList = '';
        if (!empty($availableDoctors)) {
            $doctorsList = "\n\nAvailable doctors for this service:\n" . implode("\n", array_map(fn($d) => "- {$d}", array_slice($availableDoctors, 0, 5)));
        }

        $doctorNotFoundPrompt = <<<PROMPT
The user requested an appointment with "{$doctorName}" for "{$serviceName}", but this doctor is not available for this service.{$doctorsList}

Please update your response to:
1. Politely inform the user that the requested doctor is not available for this service
2. Suggest the available doctors listed above, or ask if they'd like to schedule with a different doctor
3. Keep your response conversational and helpful

Your previous response was: "{$initialMessage}"

Provide an updated response that incorporates this information.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful scheduling assistant. Update your response based on doctor availability information provided.'],
                    ...$conversationHistory,
                    ['role' => 'assistant', 'content' => $initialMessage],
                    ['role' => 'user', 'content' => $doctorNotFoundPrompt],
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

