<?php

namespace App\Modules\Notifications\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications via Firebase Cloud Messaging.
 *
 * Prefers HTTP v1 (service-account JSON via FIREBASE_CREDENTIALS).
 * Falls back to the legacy server-key API when FCM_SERVER_KEY is set.
 */
class FcmService
{
    private const LEGACY_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';
    private const OAUTH_ENDPOINT  = 'https://oauth2.googleapis.com/token';
    private const FCM_SCOPE       = 'https://www.googleapis.com/auth/firebase.messaging';

    private static ?string $cachedAccessToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * @param  array<string, string>  $data
     */
    public function send(
        string $fcmToken,
        string $titleAr,
        string $bodyAr,
        array  $data = [],
    ): ?string {
        if ($fcmToken === '') {
            Log::warning('[FCM] Empty FCM token — push skipped.');

            return null;
        }

        if ($this->resolveCredentialsPath() !== null) {
            return $this->sendViaHttpV1($fcmToken, $titleAr, $bodyAr, $data);
        }

        return $this->sendViaLegacy($fcmToken, $titleAr, $bodyAr, $data);
    }

    // ── HTTP v1 (service-account JSON) ─────────────────────────────────────

    /**
     * @param  array<string, string>  $data
     */
    private function sendViaHttpV1(
        string $fcmToken,
        string $titleAr,
        string $bodyAr,
        array  $data,
    ): ?string {
        $credentialsPath = $this->resolveCredentialsPath();

        if ($credentialsPath === null) {
            Log::warning('[FCM] FIREBASE_CREDENTIALS file not found — push skipped.');

            return null;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true);

        if (! is_array($credentials) || empty($credentials['project_id'])) {
            Log::error('[FCM] Invalid Firebase credentials JSON.', [
                'path' => $credentialsPath,
            ]);

            return null;
        }

        $accessToken = $this->getAccessToken($credentials);

        if ($accessToken === null) {
            return null;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post(
                    'https://fcm.googleapis.com/v1/projects/' . $credentials['project_id'] . '/messages:send',
                    [
                        'message' => [
                            'token'        => $fcmToken,
                            'notification' => [
                                'title' => $titleAr,
                                'body'  => $bodyAr,
                            ],
                            'data'         => array_map('strval', $data),
                            'android'      => ['priority' => 'HIGH'],
                            'apns'         => [
                                'payload' => [
                                    'aps' => ['sound' => 'default'],
                                ],
                            ],
                        ],
                    ],
                );

            if ($response->successful()) {
                $messageId = $response->json('name');

                Log::info('[FCM] Push sent via HTTP v1.', [
                    'message_id'   => $messageId,
                    'token_prefix' => substr($fcmToken, 0, 20),
                ]);

                return is_string($messageId) ? $messageId : null;
            }

            Log::error('[FCM] HTTP v1 push failed.', [
                'status'       => $response->status(),
                'body'         => $response->body(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('[FCM] HTTP v1 exception.', [
                'error'        => $e->getMessage(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;
        }
    }

    /** @param  array<string, mixed>  $credentials */
    private function getAccessToken(array $credentials): ?string
    {
        if (self::$cachedAccessToken !== null && time() < self::$tokenExpiresAt - 60) {
            return self::$cachedAccessToken;
        }

        try {
            $jwt = $this->createServiceAccountJwt($credentials);

            $response = Http::asForm()->post(self::OAUTH_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (! $response->successful()) {
                Log::error('[FCM] Failed to obtain OAuth access token.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            self::$cachedAccessToken = $response->json('access_token');
            $expiresIn               = (int) $response->json('expires_in', 3600);
            self::$tokenExpiresAt    = time() + $expiresIn;

            return self::$cachedAccessToken;

        } catch (\Throwable $e) {
            Log::error('[FCM] OAuth token exception.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** @param  array<string, mixed>  $credentials */
    private function createServiceAccountJwt(array $credentials): string
    {
        $now = time();

        $header  = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => self::FCM_SCOPE,
            'aud'   => self::OAUTH_ENDPOINT,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $unsigned = "{$header}.{$payload}";

        openssl_sign(
            $unsigned,
            $signature,
            (string) $credentials['private_key'],
            OPENSSL_ALGO_SHA256,
        );

        return $unsigned . '.' . $this->base64UrlEncode($signature);
    }

    // ── Legacy server-key API ──────────────────────────────────────────────

    /**
     * @param  array<string, string>  $data
     */
    private function sendViaLegacy(
        string $fcmToken,
        string $titleAr,
        string $bodyAr,
        array  $data,
    ): ?string {
        $serverKey = config('notifications.fcm_server_key');

        if ($serverKey === '') {
            Log::warning('[FCM] Neither FIREBASE_CREDENTIALS nor FCM_SERVER_KEY is configured — push skipped.', [
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post(self::LEGACY_ENDPOINT, [
                'to'           => $fcmToken,
                'notification' => [
                    'title' => $titleAr,
                    'body'  => $bodyAr,
                    'sound' => 'default',
                ],
                'data'         => array_map('strval', $data),
                'priority'     => 'high',
            ]);

            if ($response->successful()) {
                $messageId = $response->json('results.0.message_id');

                Log::info('[FCM] Push sent via legacy API.', [
                    'message_id'   => $messageId,
                    'token_prefix' => substr($fcmToken, 0, 20),
                ]);

                return is_string($messageId) ? $messageId : null;
            }

            Log::error('[FCM] Legacy push failed.', [
                'status'       => $response->status(),
                'body'         => $response->body(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('[FCM] Legacy push exception.', [
                'error'        => $e->getMessage(),
                'token_prefix' => substr($fcmToken, 0, 20),
            ]);

            return null;
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function resolveCredentialsPath(): ?string
    {
        $configured = config('notifications.firebase_credentials');

        if (! is_string($configured) || $configured === '') {
            return null;
        }

        foreach ([$configured, base_path($configured), storage_path($configured)] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
