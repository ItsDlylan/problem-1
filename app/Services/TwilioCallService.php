<?php

declare(strict_types=1);

namespace App\Services;

use Twilio\TwiML\VoiceResponse;

final readonly class TwilioCallService
{
    /**
     * Generate TwiML response for greeting.
     */
    public function generateGreetingResponse(string $nextActionUrl): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->say(
            'Hello! Welcome to our appointment booking service. Please hold while we connect you.',
            ['voice' => 'alice']
        );
        $response->redirect($nextActionUrl, ['method' => 'POST']);

        return $response;
    }

    /**
     * Generate TwiML response with text-to-speech.
     */
    public function generateSayResponse(string $text, ?string $nextActionUrl = null, string $voice = 'alice'): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->say($text, ['voice' => $voice]);

        if ($nextActionUrl) {
            $response->redirect($nextActionUrl, ['method' => 'POST']);
        }

        return $response;
    }

    /**
     * Generate TwiML response to gather voice input.
     */
    public function generateGatherResponse(
        string $prompt,
        string $actionUrl,
        ?string $fallbackUrl = null,
        int $timeout = 5,
        int $maxSpeechTime = 30
    ): VoiceResponse {
        $response = new VoiceResponse();

        if ($prompt) {
            $response->say($prompt, ['voice' => 'alice']);
        }

        $gather = $response->gather([
            'input' => 'speech',
            'action' => $actionUrl,
            'method' => 'POST',
            'timeout' => $timeout,
            'speechTimeout' => 'auto',
            'maxSpeechTime' => $maxSpeechTime,
            'language' => 'en-US',
        ]);

        // Fallback if no input received
        if ($fallbackUrl) {
            $response->redirect($fallbackUrl, ['method' => 'POST']);
        } else {
            $response->say('I did not hear anything. Please try again.', ['voice' => 'alice']);
            $response->redirect($actionUrl, ['method' => 'POST']);
        }

        return $response;
    }

    /**
     * Generate TwiML response to play audio file.
     */
    public function generatePlayResponse(string $audioUrl, ?string $nextActionUrl = null): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->play($audioUrl);

        if ($nextActionUrl) {
            $response->redirect($nextActionUrl, ['method' => 'POST']);
        }

        return $response;
    }

    /**
     * Generate TwiML response for recording.
     */
    public function generateRecordResponse(string $actionUrl, int $maxLength = 60): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->record([
            'action' => $actionUrl,
            'method' => 'POST',
            'maxLength' => $maxLength,
            'recordingStatusCallback' => $actionUrl,
            'recordingStatusCallbackMethod' => 'POST',
        ]);

        return $response;
    }

    /**
     * Generate TwiML response for hangup.
     */
    public function generateHangupResponse(string $message = 'Thank you for calling. Goodbye.'): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->say($message, ['voice' => 'alice']);
        $response->hangup();

        return $response;
    }

    /**
     * Generate TwiML response with pause.
     */
    public function generatePauseResponse(int $seconds = 1): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->pause(['length' => $seconds]);

        return $response;
    }
}

