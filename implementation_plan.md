# Auto-Send WhatsApp Welcome Message via Meta Cloud API

## Problem

Currently, when a user is created, the system generates a `wa.me` link and stores it in `welcome_whatsapp_url` on the user record. The admin must **manually** open the link, which opens WhatsApp in the browser, and then **press Send**. This is tedious and error-prone.

## Goal

Replace the manual `wa.me` link approach with **automatic sending** via the **WhatsApp Cloud API by Meta**, so the welcome message is sent directly to the new user's phone from the admin's WhatsApp Business number — no manual step needed.

---

## User Review Required

> [!IMPORTANT]
> **Meta Setup Required (before this code works):**
> 1. Go to [developers.facebook.com](https://developers.facebook.com) → Create a Meta App → Select "WhatsApp"
> 2. In the WhatsApp > API Setup page, get your **Phone Number ID** and **Access Token**
> 3. For production: create a **System User** in Meta Business Manager and generate a **permanent token**
> 4. Add a test phone number to send test messages
>
> You'll need to add these values to your `.env` file (detailed below).

> [!WARNING]
> **WhatsApp Template Messages:** Meta requires that you use **pre-approved message templates** to initiate conversations with users who haven't messaged you first. You have two options:
> 1. **Use a template message** — Create a template in Meta Business Manager (recommended for production)
> 2. **Use a free-form text message** — Only works if the user has messaged your business number in the last 24 hours
>
> For a welcome message to brand-new users, **you will need a template**. I'll implement support for both approaches, defaulting to a text message for now (works for testing), with an easy switch to template mode.

---

## Open Questions

> [!IMPORTANT]
> 1. **Do you want to keep the `welcome_whatsapp_url` field as a fallback?** If the API fails, should we still generate the wa.me link so you can send manually?
> 2. **Which Graph API version are you using?** I'll default to `v21.0` (latest stable). Let me know if you need a specific version.

---

## Proposed Changes

### Auth Module — WhatsApp Infrastructure

#### [NEW] [WhatsAppCloudApiSender.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Infrastructure/WhatsApp/Senders/WhatsAppCloudApiSender.php)

New class that sends messages via Meta's WhatsApp Cloud API using Laravel's HTTP client:

- `sendTextMessage(string $to, string $body): array` — sends a free-form text message
- `sendTemplateMessage(string $to, string $templateName, array $parameters): array` — sends a template-based message
- Uses `Http::withToken()` to authenticate with the Meta Graph API
- Throws `WhatsAppSendFailedException` on errors with full error details logged

---

#### [MODIFY] [WhatsAppService.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Domain/Services/WhatsAppService.php)

Add a new `sendWelcomeMessage()` method that:
1. Builds the welcome text using the existing `buildWelcomeMessage()` method
2. Calls `WhatsAppCloudApiSender::sendTextMessage()` to actually send it
3. Still keeps `buildWelcomeLink()` as a fallback

---

#### [MODIFY] [SendWelcomeMessageOnWhatsAppUseCase.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Application/UseCases/SendWelcomeMessageOnWhatsAppUseCase.php)

Change from "generate link and store" to "send message directly":
1. Call `WhatsAppService::sendWelcomeMessage()` to auto-send
2. On success: store status as `sent` (or clear the URL field)
3. On failure: fall back to generating the `wa.me` link so admin can send manually
4. Log the result either way

---

#### [MODIFY] [SendWelcomeMessageOnWhatsAppJob.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Infrastructure/Jobs/SendWelcomeMessageOnWhatsAppJob.php)

Update the queued job to use the new auto-send approach instead of just regenerating the link.

---

#### [NEW] [WhatsAppSendFailedException.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Application/Exceptions/WhatsAppSendFailedException.php)

Custom exception for when the WhatsApp API call fails, with error details from Meta's response.

---

### Config Changes

#### [MODIFY] [config.php](file:///c:/laragon/www/marsal/app/Modules/Auth/Infrastructure/Config/config.php)

Add WhatsApp Cloud API configuration:
```php
'whatsapp' => [
    'enabled'         => (bool) env('WHATSAPP_ENABLED', false),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token'    => env('WHATSAPP_ACCESS_TOKEN'),
    'api_version'     => env('WHATSAPP_API_VERSION', 'v21.0'),
],
```

#### [MODIFY] [.env](file:///c:/laragon/www/marsal/.env)

Add new environment variables:
```env
# WhatsApp Cloud API (Meta)
WHATSAPP_ENABLED=true
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_ACCESS_TOKEN=your_access_token_here
WHATSAPP_API_VERSION=v21.0
```

#### [MODIFY] [.env.example](file:///c:/laragon/www/marsal/.env.example)

Add the same WhatsApp env variables as documentation.

---

## Verification Plan

### Automated Tests
- Run `php artisan config:cache` to verify config loads correctly
- Test the WhatsApp sender with Meta's test phone number
- Verify the fallback wa.me link still generates on API failure

### Manual Verification
- Create a new user via the API and confirm the WhatsApp message arrives automatically on their phone
- Verify logs show success/failure status
- Test with `WHATSAPP_ENABLED=false` to confirm fallback behavior works
