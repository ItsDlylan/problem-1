<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CallSession;
use App\Models\Patient;
use App\Services\AppointmentService;
use App\Services\PatientIdentificationService;
use App\Services\TwilioCallService;
use App\Services\VoiceConversationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

final readonly class TwilioWebhookController
{
    public function __construct(
        private TwilioCallService $twilioCallService,
        private PatientIdentificationService $patientIdentificationService,
        private VoiceConversationService $voiceConversationService,
        private AppointmentService $appointmentService,
    ) {
    }

    /**
     * Handle incoming call - initial greeting and patient lookup.
     */
    public function handleIncomingCall(Request $request): Response
    {
        $callSid = $request->input('CallSid');
        $from = $request->input('From');
        $to = $request->input('To');

        Log::info('Incoming Twilio call', [
            'call_sid' => $callSid,
            'from' => $from,
            'to' => $to,
        ]);

        // Create or get call session
        $callSession = CallSession::firstOrCreate(
            ['call_sid' => $callSid],
            [
                'phone_number' => $from,
                'status' => 'greeting',
                'conversation_state' => [],
                'metadata' => ['to' => $to],
            ]
        );

        // Look up patient by phone number
        $patient = $this->patientIdentificationService->findByPhoneNumber($from);

        if ($patient) {
            // Patient found - update session and ask for verification
            $callSession->update([
                'patient_id' => $patient->id,
                'status' => 'identifying',
            ]);

            $response = new VoiceResponse();
            $response->say(
                "Hello! I found your account. To verify your identity, please tell me your first and last name.",
                ['voice' => 'alice']
            );

            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSid]);
            $response->gather([
                'input' => 'speech',
                'action' => $gatherUrl,
                'method' => 'POST',
                'timeout' => 5,
                'speechTimeout' => 'auto',
                'maxSpeechTime' => 10,
            ]);

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        // Patient not found - ask for information to create account
        $callSession->update(['status' => 'identifying']);

        $response = new VoiceResponse();
        $response->say(
            "Hello! Welcome to our appointment booking service. I don't have your information on file. Please tell me your first and last name.",
            ['voice' => 'alice']
        );

        $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSid]);
        $response->gather([
            'input' => 'speech',
            'action' => $gatherUrl,
            'method' => 'POST',
            'timeout' => 5,
            'speechTimeout' => 'auto',
            'maxSpeechTime' => 10,
        ]);

        return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Handle voice input from Gather.
     */
    public function handleVoiceInput(Request $request, string $callSid): Response
    {
        // Get input from either speech or DTMF (keypad)
        $speechResult = $request->input('SpeechResult');
        $dtmfDigits = $request->input('Digits');
        
        // Use DTMF if available (for insurance card number), otherwise use speech
        $input = $dtmfDigits ?? $speechResult;
        
        $callSession = CallSession::where('call_sid', $callSid)->firstOrFail();

        // Comprehensive logging for debugging
        Log::info('Voice input received', [
            'call_sid' => $callSid,
            'call_status' => $callSession->status,
            'speech_result' => $speechResult,
            'speech_result_type' => gettype($speechResult),
            'speech_result_empty' => empty($speechResult),
            'dtmf_digits' => $dtmfDigits,
            'dtmf_digits_type' => gettype($dtmfDigits),
            'dtmf_digits_empty' => empty($dtmfDigits),
            'final_input' => $input,
            'all_request_inputs' => $request->all(),
            'request_method' => $request->method(),
            'request_headers' => $request->headers->all(),
        ]);

        $conversationState = $callSession->conversation_state ?? [];

        // Handle different states
        switch ($callSession->status) {
            case 'identifying':
                return $this->handleIdentifyingState($request, $callSession, $input, $conversationState);

            case 'booking':
                // For booking, use speech only (not DTMF)
                return $this->handleBookingState($request, $callSession, $speechResult ?? $input, $conversationState);

            case 'confirming':
                // For confirming, use speech only (not DTMF)
                return $this->handleConfirmingState($request, $callSession, $speechResult ?? $input, $conversationState);

            default:
                // Fallback to greeting
                return $this->handleIncomingCall($request);
        }
    }

    /**
     * Handle identifying state - collect patient information.
     */
    private function handleIdentifyingState(
        Request $request,
        CallSession $callSession,
        ?string $input,
        array $conversationState
    ): Response {
        // Handle empty or missing input (timeout, no input, etc.)
        if (! $input || trim($input) === '') {
            // Determine what we're asking for based on conversation state
            $identifying = $conversationState['identifying'] ?? [];
            $prompt = "I did not hear anything. ";
            
            if (! isset($identifying['first_name']) || ! isset($identifying['last_name'])) {
                $prompt .= "Please tell me your first and last name.";
            } elseif (! isset($identifying['insurance_card_number'])) {
                $prompt .= "Please enter your insurance card number using your keypad, or say it out loud.";
            } else {
                $prompt .= "Please try again.";
            }

            $response = new VoiceResponse();
            $response->say($prompt, ['voice' => 'alice']);

            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
            
            // For insurance card, allow both DTMF and speech
            if (isset($identifying['first_name']) && isset($identifying['last_name'])) {
                $response->gather([
                    'input' => 'dtmf speech',
                    'action' => $gatherUrl,
                    'method' => 'POST',
                    'timeout' => 10,
                    'speechTimeout' => 'auto',
                    'maxSpeechTime' => 15,
                    'finishOnKey' => '#',
                ]);
            } else {
                $response->gather([
                    'input' => 'speech',
                    'action' => $gatherUrl,
                    'method' => 'POST',
                    'timeout' => 5,
                    'speechTimeout' => 'auto',
                    'maxSpeechTime' => 10,
                ]);
            }

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        // Track what information we've collected
        if (! isset($conversationState['identifying'])) {
            $conversationState['identifying'] = [];
        }

        $identifying = $conversationState['identifying'];

        // Collect information: first_name + last_name together -> insurance_card_number
        if (! isset($identifying['first_name']) || ! isset($identifying['last_name'])) {
            // Parse the combined name input
            $nameParts = preg_split('/\s+/', trim($input), 2);
            
            if (count($nameParts) >= 2) {
                $identifying['first_name'] = strtolower(trim($nameParts[0]));
                $identifying['last_name'] = strtolower(trim($nameParts[1]));
            } else {
                // If only one word provided, treat it as first name and ask for last name
                $identifying['first_name'] = strtolower(trim($input));
                
                $response = new VoiceResponse();
                $response->say("I heard your first name. Please tell me your last name.", ['voice' => 'alice']);

                $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
                $response->gather([
                    'input' => 'speech',
                    'action' => $gatherUrl,
                    'method' => 'POST',
                    'timeout' => 5,
                    'speechTimeout' => 'auto',
                    'maxSpeechTime' => 10,
                ]);

                $callSession->update(['conversation_state' => array_merge($conversationState, ['identifying' => $identifying])]);
                return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
            }
            
            $conversationState['identifying'] = $identifying;
            $callSession->update(['conversation_state' => $conversationState]);

            $response = new VoiceResponse();
            $response->say("Thank you. Now please enter your insurance card number using your keypad, or say it out loud. Press pound when finished.", ['voice' => 'alice']);

            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
            $response->gather([
                'input' => 'dtmf speech',
                'action' => $gatherUrl,
                'method' => 'POST',
                'timeout' => 10,
                'speechTimeout' => 'auto',
                'maxSpeechTime' => 15,
                'finishOnKey' => '#',
            ]);

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        if (! isset($identifying['insurance_card_number'])) {
            // Clean up DTMF input (remove # if present)
            $insuranceNumber = trim($input, '#');
            $identifying['insurance_card_number'] = $insuranceNumber;
            $conversationState['identifying'] = $identifying;

            // Now verify or create patient
            $firstName = $identifying['first_name'];
            $lastName = $identifying['last_name'];
            $insuranceCardNumber = $identifying['insurance_card_number'];
            $phoneNumber = $callSession->phone_number;

            $patient = $callSession->patient_id
                ? Patient::find($callSession->patient_id)
                : null;

            // TODO: Re-enable name-based authentication verification later
            // Currently disabled due to potential speech recognition errors with names
            // if ($patient) {
            //     // Verify existing patient
            //     $isValid = $this->patientIdentificationService->verifyPatientIdentity(
            //         $patient,
            //         $firstName,
            //         $lastName,
            //         $insuranceCardNumber
            //     );
            //
            //     if (! $isValid) {
            //         $callSession->update(['status' => 'failed', 'conversation_state' => $conversationState]);
            //
            //         $response = new VoiceResponse();
            //         $response->say(
            //             "I'm sorry, the information you provided does not match our records. Please call back and try again. Goodbye.",
            //             ['voice' => 'alice']
            //         );
            //         $response->hangup();
            //
            //         return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
            //     }
            // } else {
            //     // Create new patient
            //     $patient = $this->patientIdentificationService->createOrUpdateFromCall(
            //         $phoneNumber,
            //         $firstName,
            //         $lastName,
            //         $insuranceCardNumber
            //     );
            // }

            // For now, create or update patient without verification
            if (! $patient) {
                // Create new patient
                $patient = $this->patientIdentificationService->createOrUpdateFromCall(
                    $phoneNumber,
                    $firstName,
                    $lastName,
                    $insuranceCardNumber
                );
            } else {
                // Update existing patient with provided information (without verification)
                $patient->update([
                    'first_name' => ucfirst($firstName),
                    'last_name' => ucfirst($lastName),
                    'insurance_card_number' => $insuranceCardNumber,
                ]);
            }

            // Update call session with patient
            $callSession->update([
                'patient_id' => $patient->id,
                'status' => 'booking',
                'conversation_state' => $conversationState,
            ]);

            // Initialize conversation history
            $this->voiceConversationService->updateConversationHistory($callSession->call_sid, []);

            // Move to booking state
            $response = new VoiceResponse();
            $response->say(
                "Thank you. How can I help you schedule an appointment today?",
                ['voice' => 'alice']
            );

            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
            $response->gather([
                'input' => 'speech',
                'action' => $gatherUrl,
                'method' => 'POST',
                'timeout' => 10,
                'speechTimeout' => 'auto',
                'maxSpeechTime' => 30,
            ]);

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        // Should not reach here, but handle gracefully
        return $this->handleBookingState($request, $callSession, $input, $conversationState);
    }

    /**
     * Handle booking state - appointment booking conversation.
     */
    private function handleBookingState(
        Request $request,
        CallSession $callSession,
        ?string $speechResult,
        array $conversationState
    ): Response {
        if (! $speechResult) {
            return $this->generateErrorResponse('I did not hear anything. Please try again.');
        }

        $patient = Patient::findOrFail($callSession->patient_id);

        // Get conversation history
        $conversationHistory = $this->voiceConversationService->getConversationHistory($callSession->call_sid);

        // Process the voice message using VoiceConversationService
        try {
            $result = $this->voiceConversationService->processVoiceMessage(
                $speechResult,
                $conversationHistory,
                $patient
            );

            // Update conversation history
            $conversationHistory[] = ['role' => 'user', 'content' => $speechResult];
            $conversationHistory[] = ['role' => 'assistant', 'content' => $result['message']];
            $this->voiceConversationService->updateConversationHistory($callSession->call_sid, $conversationHistory);

            // Handle partial or complete appointment details
            $extractedDetails = $result['extractedDetails'] ?? null;
            $collectedInfo = $conversationState['collected_info'] ?? [];

            // Merge new information with previously collected information
            if ($extractedDetails) {
                if (isset($extractedDetails['serviceOfferingId'])) {
                    $collectedInfo['serviceOfferingId'] = $extractedDetails['serviceOfferingId'];
                    $collectedInfo['service'] = $extractedDetails['service'] ?? null;
                    $collectedInfo['serviceOffering'] = $extractedDetails['serviceOffering'] ?? null;
                    $collectedInfo['has_service'] = true;
                }
                
                if (isset($extractedDetails['datetime'])) {
                    $collectedInfo['datetime'] = $extractedDetails['datetime'];
                    $collectedInfo['has_datetime'] = true;
                }
                
                // Update conversation state with collected information
                $conversationState['collected_info'] = $collectedInfo;
                $callSession->update(['conversation_state' => $conversationState]);
            }

            // Check if we have complete appointment details ready for confirmation
            if (isset($collectedInfo['serviceOfferingId']) && isset($collectedInfo['datetime']) && 
                $collectedInfo['serviceOfferingId'] && $collectedInfo['datetime']) {
                // Build complete appointment details from collected info
                $completeDetails = [
                    'service' => $collectedInfo['service'] ?? 'appointment',
                    'datetime' => $collectedInfo['datetime'],
                    'serviceOfferingId' => $collectedInfo['serviceOfferingId'],
                    'serviceOffering' => $collectedInfo['serviceOffering'] ?? null,
                ];
                
                // Store complete appointment details in conversation state
                $conversationState['appointment_details'] = $completeDetails;
                $callSession->update([
                    'status' => 'confirming',
                    'conversation_state' => $conversationState,
                ]);

                Log::info('Entering confirmation state', [
                    'call_sid' => $callSession->call_sid,
                    'appointment_details' => $completeDetails,
                    'conversation_state' => $conversationState,
                ]);

                // Ask for confirmation
                $response = new VoiceResponse();
                $serviceName = $completeDetails['service'] ?? 'appointment';
                $datetime = $completeDetails['datetime'] ?? '';
                // Format datetime in America/Chicago timezone
                $formattedDate = $datetime 
                    ? Carbon::parse($datetime, 'America/Chicago')
                        ->setTimezone('America/Chicago')
                        ->format('l, F j, Y \a\t g:i A')
                    : '';

                $confirmationMessage = "I found a {$serviceName} appointment available on {$formattedDate}. Would you like to confirm this appointment? Please say yes or no.";
                
                Log::info('Sending confirmation prompt', [
                    'call_sid' => $callSession->call_sid,
                    'confirmation_message' => $confirmationMessage,
                ]);

                $response->say($confirmationMessage, ['voice' => 'alice']);

                $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
                $response->gather([
                    'input' => 'speech',
                    'action' => $gatherUrl,
                    'method' => 'POST',
                    'timeout' => 5,
                    'speechTimeout' => 'auto',
                    'maxSpeechTime' => 10,
                ]);

                return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
            }

            // Continue conversation - use Twilio's built-in TTS (more reliable)
            $response = new VoiceResponse();
            $response->say($result['message'], ['voice' => 'alice']);

            // Continue gathering input
            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
            $response->gather([
                'input' => 'speech',
                'action' => $gatherUrl,
                'method' => 'POST',
                'timeout' => 10,
                'speechTimeout' => 'auto',
                'maxSpeechTime' => 30,
            ]);

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        } catch (\Exception $e) {
            Log::error('Error processing voice message', [
                'call_sid' => $callSession->call_sid,
                'error' => $e->getMessage(),
            ]);

            return $this->generateErrorResponse('I apologize, but I encountered an error. Please try again.');
        }
    }

    /**
     * Handle confirming state - confirm appointment creation.
     */
    private function handleConfirmingState(
        Request $request,
        CallSession $callSession,
        ?string $speechResult,
        array $conversationState
    ): Response {
        // Detailed logging for confirmation state
        Log::info('Confirmation state - input received', [
            'call_sid' => $callSession->call_sid,
            'speech_result_raw' => $speechResult,
            'speech_result_type' => gettype($speechResult),
            'speech_result_empty' => empty($speechResult),
            'speech_result_is_null' => is_null($speechResult),
            'speech_result_trimmed' => $speechResult ? trim($speechResult) : null,
            'all_request_inputs' => $request->all(),
            'conversation_state' => $conversationState,
        ]);

        if (! $speechResult || trim($speechResult) === '') {
            Log::warning('Confirmation state - empty speech result', [
                'call_sid' => $callSession->call_sid,
                'speech_result' => $speechResult,
                'request_all' => $request->all(),
            ]);

            return $this->generateErrorResponse('I did not hear anything. Please say yes or no.');
        }

        // Use AI to interpret the confirmation response
        $interpretation = $this->voiceConversationService->interpretConfirmationResponse($speechResult);

        Log::info('Confirmation state - AI interpretation', [
            'call_sid' => $callSession->call_sid,
            'raw_input' => $speechResult,
            'interpretation' => $interpretation,
        ]);

        $confirmed = $interpretation['is_confirmed'];
        $denied = $interpretation['is_denied'];
        $isUnclear = $interpretation['is_unclear'];

        if ($isUnclear) {
            // Unclear response
            Log::warning('Confirmation state - unclear response', [
                'call_sid' => $callSession->call_sid,
                'raw_speech' => $speechResult,
                'interpretation' => $interpretation,
            ]);

            $response = new VoiceResponse();
            $response->say('I did not understand. Please say yes to confirm or no to cancel.', ['voice' => 'alice']);

            $gatherUrl = route('twilio.voice.gather', ['call_sid' => $callSession->call_sid]);
            $response->gather([
                'input' => 'speech',
                'action' => $gatherUrl,
                'method' => 'POST',
                'timeout' => 5,
                'speechTimeout' => 'auto',
                'maxSpeechTime' => 10,
            ]);

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        if (! $confirmed) {
            // User cancelled
            $callSession->update(['status' => 'completed']);

            $response = new VoiceResponse();
            $response->say('Appointment booking cancelled. Thank you for calling. Goodbye.', ['voice' => 'alice']);
            $response->hangup();

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        }

        // Create appointment
        $appointmentDetails = $conversationState['appointment_details'] ?? null;

        if (! $appointmentDetails || ! isset($appointmentDetails['serviceOfferingId']) || ! isset($appointmentDetails['datetime'])) {
            $callSession->update(['status' => 'failed']);

            return $this->generateErrorResponse('I apologize, but I could not find the appointment details. Please call back and try again.');
        }

        try {
            $patient = Patient::findOrFail($callSession->patient_id);

            $appointment = $this->appointmentService->createFromChat(
                [
                    'serviceOfferingId' => $appointmentDetails['serviceOfferingId'],
                    'datetime' => $appointmentDetails['datetime'],
                ],
                $patient
            );

            // Clear conversation history
            $this->voiceConversationService->clearConversationHistory($callSession->call_sid);

            // Update call session
            $callSession->update([
                'status' => 'completed',
                'metadata' => array_merge($callSession->metadata ?? [], ['appointment_id' => $appointment->id]),
            ]);

            // Success message
            $serviceName = $appointmentDetails['service'] ?? 'appointment';
            $datetime = $appointmentDetails['datetime'] ?? '';
            // Format datetime in America/Chicago timezone
            $formattedDate = $datetime 
                ? Carbon::parse($datetime, 'America/Chicago')
                    ->setTimezone('America/Chicago')
                    ->format('l, F j, Y \a\t g:i A')
                : '';

            $response = new VoiceResponse();
            $response->say(
                "Great! Your {$serviceName} appointment has been confirmed for {$formattedDate}. You will receive a confirmation email shortly. Thank you for calling. Goodbye!",
                ['voice' => 'alice']
            );
            $response->hangup();

            return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
        } catch (\Exception $e) {
            Log::error('Error creating appointment', [
                'call_sid' => $callSession->call_sid,
                'error' => $e->getMessage(),
            ]);

            $callSession->update(['status' => 'failed']);

            return $this->generateErrorResponse('I apologize, but I encountered an error while creating your appointment. Please call back and try again.');
        }
    }

    /**
     * Handle call status updates.
     */
    public function handleCallStatus(Request $request): Response
    {
        $callSid = $request->input('CallSid');
        $callStatus = $request->input('CallStatus');

        Log::info('Call status update', [
            'call_sid' => $callSid,
            'status' => $callStatus,
        ]);

        // Update call session if exists
        $callSession = CallSession::where('call_sid', $callSid)->first();

        if ($callSession && in_array($callStatus, ['completed', 'busy', 'no-answer', 'failed', 'canceled'], true)) {
            // Clean up conversation history for completed calls
            if ($callStatus === 'completed') {
                $this->voiceConversationService->clearConversationHistory($callSid);
            }
        }

        return response('OK', 200);
    }

    /**
     * Generate error response.
     */
    private function generateErrorResponse(string $message): Response
    {
        $response = new VoiceResponse();
        $response->say($message, ['voice' => 'alice']);
        $response->hangup();

        return response($response->asXML(), 200, ['Content-Type' => 'text/xml']);
    }
}
