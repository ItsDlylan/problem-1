# Twilio Voice Appointment Booking Setup Guide

This guide will walk you through setting up the Twilio voice appointment booking system.

## Prerequisites

- Laravel application running
- Twilio account (sign up at https://www.twilio.com/try-twilio)
- OpenAI API key (for speech-to-text and text-to-speech)
- Publicly accessible URL for webhooks (use ngrok for local development)

## Step 1: Run Database Migrations

First, run the migrations to add the necessary database tables:

```bash
php artisan migrate
```

This will create:
- `insurance_card_number` column in the `patients` table
- `call_sessions` table for tracking call state

## Step 2: Configure Environment Variables

Add the following variables to your `.env` file:

```env
# Twilio Configuration
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890

# OpenAI Configuration (if not already set)
OPENAI_API_KEY=your_openai_api_key_here

# Optional: Disable webhook validation in local development
# Set to false to skip signature validation during local testing
# TWILIO_VALIDATE_WEBHOOKS=true
```

### Getting Twilio Credentials

1. **Sign up for Twilio**: Go to https://www.twilio.com/try-twilio
2. **Get Account SID and Auth Token**:
   - Log into Twilio Console
   - Go to Account → Account Info
   - Copy your `Account SID` and `Auth Token`
3. **Get a Phone Number**:
   - Go to Phone Numbers → Manage → Buy a number
   - Purchase a phone number (or use a trial number)
   - Copy the phone number (include country code, e.g., `+1234567890`)

## Step 3: Configure Twilio Webhook

You need to configure your Twilio phone number to send webhooks to your application.

### For Production

1. Log into Twilio Console
2. Go to Phone Numbers → Manage → Active numbers
3. Click on your phone number
4. Under "Voice & Fax" section, set:
   - **A CALL COMES IN**: `https://yourdomain.com/api/twilio/voice`
   - **CALL STATUS CHANGES**: `https://yourdomain.com/api/twilio/voice/status`
5. Save the configuration

### For Local Development (using ngrok)

1. **Install ngrok**: https://ngrok.com/download

2. **Start your Laravel server**:
   ```bash
   php artisan serve
   ```

3. **Start ngrok**:
   ```bash
   ngrok http 8000
   ```

4. **Copy the ngrok URL** (e.g., `https://abc123.ngrok.io`)

5. **Configure Twilio webhook**:
   - In Twilio Console → Phone Numbers → Your Number
   - Set **A CALL COMES IN**: `https://abc123.ngrok.io/api/twilio/voice`
   - Set **CALL STATUS CHANGES**: `https://abc123.ngrok.io/api/twilio/voice/status`
   - Save

6. **Note**: ngrok URLs change each time you restart ngrok. Update Twilio webhook URL if needed.

## Step 4: Ensure Storage is Linked

Make sure the storage link exists for TTS audio files:

```bash
php artisan storage:link
```

## Step 5: Test the System

### Test Call Flow

1. **Call your Twilio phone number** from any phone
2. **Follow the prompts**:
   - System will greet you
   - If patient exists: Ask for first name, last name, insurance card number
   - If patient doesn't exist: Collect same information to create account
   - Once verified: Start booking conversation
   - Provide service name and preferred date/time
   - Confirm appointment details
   - Appointment will be created

### Expected Call Flow

```
1. Call comes in → System looks up patient by phone number
2. If found: "Hello! I found your account. To verify your identity, please tell me your first name."
3. Collect: First name → Last name → Insurance card number
4. Verify identity matches records
5. "Thank you for verifying your identity. How can I help you schedule an appointment today?"
6. Patient describes appointment needs (e.g., "I need a checkup next Tuesday at 2pm")
7. System processes request and confirms details
8. "I found a [service] appointment available on [date/time]. Would you like to confirm?"
9. Patient confirms → Appointment created → "Great! Your appointment has been confirmed..."
```

## Step 6: Monitor and Debug

### View Logs

Check Laravel logs for call activity:

```bash
# Using Laravel Pail (if installed)
php artisan pail

# Or view log file
tail -f storage/logs/laravel.log
```

### Check Call Sessions

You can query the database to see call sessions:

```bash
php artisan tinker
```

```php
// View recent call sessions
\App\Models\CallSession::latest()->take(10)->get();

// View specific call session
\App\Models\CallSession::where('call_sid', 'CAxxxxx')->first();
```

## Troubleshooting

### Issue: "Unauthorized" error when Twilio calls webhook

**Solution**: 
- Check that webhook URL is publicly accessible
- Verify Twilio credentials in `.env`
- In local development, you may need to disable webhook validation temporarily:
  ```env
  TWILIO_VALIDATE_WEBHOOKS=false
  ```
  (Only for local testing - always enable in production!)

### Issue: "Failed to transcribe audio" error

**Solution**:
- Verify OpenAI API key is set correctly
- Check OpenAI account has credits
- Ensure audio file is accessible from your server

### Issue: Patient not found even though they exist

**Solution**:
- Check phone number format in database matches Twilio format
- Phone numbers are normalized (removes formatting, handles country codes)
- Verify patient has `insurance_card_number` set in database

### Issue: Call hangs or doesn't respond

**Solution**:
- Check Laravel logs for errors
- Verify webhook URL is correct in Twilio Console
- Ensure Laravel server is running and accessible
- Check ngrok is running (if using for local development)

## Security Notes

1. **Webhook Validation**: The system validates Twilio webhook signatures by default. Only disable in local development.

2. **Environment Variables**: Never commit `.env` file with real credentials to version control.

3. **HTTPS**: Always use HTTPS in production for webhook endpoints.

4. **Rate Limiting**: Consider adding rate limiting to webhook endpoints in production.

## Next Steps

- **Add more voice options**: Customize TTS voice in `VoiceConversationService::convertTextToSpeech()`
- **Add call recording**: Enable recording in `TwilioWebhookController` if needed
- **Add outbound calls**: Extend system to make outbound calls for reminders
- **Add multi-language support**: Extend system to support multiple languages

## API Endpoints

The system exposes these webhook endpoints:

- `POST /api/twilio/voice` - Handle incoming calls
- `POST /api/twilio/voice/gather/{call_sid}` - Handle voice input
- `POST /api/twilio/voice/status` - Handle call status updates

These endpoints are protected by `ValidateTwilioWebhook` middleware which validates Twilio signatures.

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Twilio Console → Monitor → Logs for call details
3. Review call session records in database

