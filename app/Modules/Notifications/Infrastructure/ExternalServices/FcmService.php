<?php

namespace App\Modules\Notifications\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FcmService
 *
 * Wraps the Firebase Cloud Messaging HTTP v1 API.
 * Reads credentials from environment variables:
 *   FCM_SERVER_KEY  — legacy server key  (used as Bearer token for simple setup)
 *
 * To upgrade to FCM v1 OAuth2: swap the Authorization header with a
 * Google service-account access token obtained via kreait/firebase-php.
 *
 * Returns the FCM message ID on success, or null on failure (failure is logged).
 */
class FcmService
{
    private const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Send a push notification to a single FCM token.
     *
     * @param  array<string, string>  $data  Additional data payload for the app
     * @return string|null FCM message ID on success, null on failure
     */
    public function send(
        string $fcmToken,
        string $titleAr,
        string $bodyAr,
        array  $data = [],
    ): ?string {
        $serverKey = config('notifications.fcm_server_key');

        if (empty($serverKey)) {
            Log::warning('[FCM] FCM_SERVER_KEY is not configured — push skipped.', [
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;
        }

        if (empty($fcmToken)) {
            Log::warning('[FCM] Empty FCM token — push skipped.');
            return null;
        }

        try {
            // FCM legacy API requires all data payload values to be strings
            $stringData = array_map('strval', $data);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post(self::FCM_ENDPOINT, [
                'to'           => $fcmToken,
                'notification' => [
                    'title' => $titleAr,
                    'body'  => $bodyAr,
                    'sound' => 'default',
                ],
                'data'         => $stringData,
                'priority'     => 'high',
            ]);

            if ($response->successful()) {
                $body      = $response->json();
                $messageId = $body['results'][0]['message_id'] ?? null;

                Log::info('[FCM] Push sent successfully.', [
                    'message_id'   => $messageId,
                    'token_prefix' => substr($fcmToken, 0, 20),
                ]);

                return $messageId;
            }

            Log::error('[FCM] Push failed.', [
                'status'       => $response->status(),
                'body'         => $response->body(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('[FCM] Exception while sending push.', [
                'error'        => $e->getMessage(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;
        }
    }
}
