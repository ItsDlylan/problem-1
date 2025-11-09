<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

final readonly class VoiceConversationService
{
    public function __construct(
        private ChatService $chatService,
    ) {
    }

    /**
     * Process a voice message (transcribed text) and return OpenAI response with extracted appointment details.
     * Reuses ChatService logic for conversation handling.
     *
     * @param  array<string, array{role: string, content: string}>  $conversationHistory
     * @return array{message: string, extractedDetails: array{service?: string, datetime?: string, serviceOfferingId?: int, isAvailable?: bool, alternativeTimes?: array<int, array{startAt: string, endAt: string}>}|null}
     */
    public function processVoiceMessage(string $transcribedText, array $conversationHistory, Patient $patient): array
    {
        // Reuse ChatService's processMessage logic
        return $this->chatService->processMessage($transcribedText, $conversationHistory, $patient);
    }

    /**
     * Convert speech to text using OpenAI Whisper API.
     *
     * @param  string  $audioUrl  URL to the audio file (from Twilio recording)
     * @return string  Transcribed text
     */
    public function convertSpeechToText(string $audioUrl): string
    {
        try {
            // Download the audio file from Twilio
            $audioContent = Http::timeout(30)->get($audioUrl)->body();

            // Store temporarily
            $tempPath = 'temp/voice_'.uniqid().'.wav';
            Storage::disk('local')->put($tempPath, $audioContent);

            $fullPath = Storage::disk('local')->path($tempPath);

            try {
                // Use OpenAI Whisper API for transcription
                $response = OpenAI::audio()->transcriptions()->create([
                    'model' => 'whisper-1',
                    'file' => fopen($fullPath, 'r'),
                    'response_format' => 'text',
                ]);

                $transcription = is_string($response) ? $response : (string) $response;

                return trim($transcription);
            } finally {
                // Clean up temporary file
                Storage::disk('local')->delete($tempPath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to convert speech to text', [
                'audio_url' => $audioUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to transcribe audio: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Convert text to speech using OpenAI TTS API.
     * Returns the audio file path or URL.
     *
     * @param  string  $text  Text to convert to speech
     * @param  string  $voice  Voice model to use (alloy, echo, fable, onyx, nova, shimmer)
     * @return string  Path to the generated audio file
     */
    public function convertTextToSpeech(string $text, string $voice = 'nova'): string
    {
        try {
            // Validate voice parameter
            $allowedVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
            if (! in_array($voice, $allowedVoices, true)) {
                $voice = 'nova';
            }

            // Use OpenAI TTS API
            $response = OpenAI::audio()->speech()->create([
                'model' => 'tts-1',
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]);

            // Store the audio file - response is a stream, convert to string
            $audioPath = 'voice/tts_'.uniqid().'.mp3';
            $audioContent = is_string($response) ? $response : (string) $response;
            Storage::disk('public')->put($audioPath, $audioContent);

            // Return the public URL
            return Storage::disk('public')->url($audioPath);
        } catch (\Exception $e) {
            Log::error('Failed to convert text to speech', [
                'text' => substr($text, 0, 100),
                'voice' => $voice,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate speech: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Interpret a yes/no response using AI to handle variations, punctuation, and natural language.
     *
     * @param  string  $response  The user's response (e.g., "Yes.", "yeah", "no way", etc.)
     * @return array{is_confirmed: bool, is_denied: bool, is_unclear: bool, interpretation: string}
     */
    public function interpretConfirmationResponse(string $response): array
    {
        try {
            $prompt = "You are interpreting a user's response to a yes/no question. The user was asked to confirm an appointment booking.

User's response: \"{$response}\"

Determine if this is:
- A CONFIRMATION (yes, yeah, yep, sure, okay, ok, confirm, correct, that's right, absolutely, definitely, sounds good, etc.)
- A DENIAL (no, nope, cancel, don't, not, wrong, incorrect, etc.)
- UNCLEAR (anything else that doesn't clearly indicate yes or no)

Respond with ONLY valid JSON in this exact format:
{
  \"is_confirmed\": true/false,
  \"is_denied\": true/false,
  \"is_unclear\": true/false,
  \"interpretation\": \"brief explanation of why\"
}

Only one of is_confirmed, is_denied, or is_unclear should be true. If the response is clearly yes, set is_confirmed=true. If clearly no, set is_denied=true. Otherwise, set is_unclear=true.";

            $messages = [
                ['role' => 'system', 'content' => $prompt],
            ];

            $openAiResponse = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.3, // Lower temperature for more consistent interpretation
            ]);

            $content = $openAiResponse->choices[0]->message->content;

            // Extract JSON from response
            $parsedContent = $this->extractJsonFromResponse($content);

            if (! is_array($parsedContent)) {
                Log::warning('Failed to parse AI confirmation interpretation', [
                    'response' => $response,
                    'ai_output' => $content,
                ]);

                // Fallback: try simple string matching
                return $this->fallbackConfirmationInterpretation($response);
            }

            return [
                'is_confirmed' => (bool) ($parsedContent['is_confirmed'] ?? false),
                'is_denied' => (bool) ($parsedContent['is_denied'] ?? false),
                'is_unclear' => (bool) ($parsedContent['is_unclear'] ?? false),
                'interpretation' => $parsedContent['interpretation'] ?? 'Unable to interpret',
            ];
        } catch (\Exception $e) {
            Log::error('Error interpreting confirmation response with AI', [
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            // Fallback to simple string matching if AI fails
            return $this->fallbackConfirmationInterpretation($response);
        }
    }

    /**
     * Fallback confirmation interpretation using simple string matching.
     *
     * @param  string  $response
     * @return array{is_confirmed: bool, is_denied: bool, is_unclear: bool, interpretation: string}
     */
    private function fallbackConfirmationInterpretation(string $response): array
    {
        $normalized = strtolower(trim($response));
        // Remove punctuation
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);

        $confirmations = ['yes', 'yeah', 'yep', 'sure', 'okay', 'ok', 'confirm', 'correct', 'right', 'absolutely', 'definitely'];
        $denials = ['no', 'nope', 'cancel', 'dont', 'not', 'wrong', 'incorrect'];

        $isConfirmed = false;
        $isDenied = false;

        foreach ($confirmations as $confirmation) {
            if (str_contains($normalized, $confirmation)) {
                $isConfirmed = true;
                break;
            }
        }

        foreach ($denials as $denial) {
            if (str_contains($normalized, $denial)) {
                $isDenied = true;
                break;
            }
        }

        return [
            'is_confirmed' => $isConfirmed && ! $isDenied,
            'is_denied' => $isDenied && ! $isConfirmed,
            'is_unclear' => ! $isConfirmed && ! $isDenied,
            'interpretation' => 'Fallback string matching',
        ];
    }

    /**
     * Extract JSON from AI response, handling cases where JSON is wrapped in markdown or other text.
     *
     * @param  string  $content
     * @return array<string, mixed>|null
     */
    private function extractJsonFromResponse(string $content): ?array
    {
        // Try to find JSON in the response
        // First, try to parse the entire content as JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to find JSON object in the text
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Get conversation history for a call session.
     *
     * @param  string  $callSid  Twilio call SID
     * @return array<string, array{role: string, content: string}>
     */
    public function getConversationHistory(string $callSid): array
    {
        $cacheKey = "voice_conversation_{$callSid}";

        return \Illuminate\Support\Facades\Cache::get($cacheKey, []);
    }

    /**
     * Update conversation history for a call session.
     *
     * @param  string  $callSid  Twilio call SID
     * @param  array<string, array{role: string, content: string}>  $conversationHistory
     */
    public function updateConversationHistory(string $callSid, array $conversationHistory): void
    {
        $cacheKey = "voice_conversation_{$callSid}";

        // Keep last 20 messages to avoid token limits
        $conversationHistory = array_slice($conversationHistory, -20);

        // Store for 2 hours (typical call duration)
        \Illuminate\Support\Facades\Cache::put($cacheKey, $conversationHistory, now()->addHours(2));
    }

    /**
     * Clear conversation history for a call session.
     *
     * @param  string  $callSid  Twilio call SID
     */
    public function clearConversationHistory(string $callSid): void
    {
        $cacheKey = "voice_conversation_{$callSid}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
}

