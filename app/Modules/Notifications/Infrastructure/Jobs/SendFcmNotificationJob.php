<?php

namespace App\Modules\Notifications\Infrastructure\Jobs;

use App\Modules\Notifications\Infrastructure\Database\Models\Notification;
use App\Modules\Notifications\Infrastructure\ExternalServices\FcmService;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendFcmNotificationJob
 *
 * Queued on the 'notifications' queue.
 * Resolves the user's FCM token, calls FcmService::send(), and
 * updates the notification row with the result.
 *
 * Retry strategy: 3 attempts with exponential back-off.
 * Idempotent: checks sent_via_fcm before sending to avoid duplicates on retry.
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private string $notificationId,
        private string $userId,
        private string $titleAr,
        private string $bodyAr,
        private array  $data = [],
    ) {}

    public function handle(FcmService $fcmService): void
    {
        // Idempotency guard — skip if already sent (e.g. retry after partial success)
        $notification = Notification::query()
            ->where('notification_id', $this->notificationId)
            ->first();

        if (! $notification) {
            Log::warning('[FCM Job] Notification record not found — skipping.', [
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        if ($notification->sent_via_fcm && $notification->fcm_message_id) {
            Log::info('[FCM Job] Already sent — skipping duplicate.', [
                'notification_id' => $this->notificationId,
                'fcm_message_id'  => $notification->fcm_message_id,
            ]);
            return;
        }

        // Resolve the user's FCM token
        $user = User::query()
            ->where('user_id', $this->userId)
            ->select(['user_id', 'fcm_token'])
            ->first();

        if (! $user || empty($user->fcm_token)) {
            Log::warning('[FCM Job] User has no FCM token — skipping push.', [
                'user_id'         => $this->userId,
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        // Send via FCM
        $messageId = $fcmService->send(
            fcmToken: $user->fcm_token,
            titleAr:  $this->titleAr,
            bodyAr:   $this->bodyAr,
            data:     $this->data,
        );

        // Update the notification row with result
        $notification->update([
            'sent_via_fcm'   => $messageId !== null,
            'fcm_message_id' => $messageId,
        ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[FCM Job] All retries exhausted.', [
            'notification_id' => $this->notificationId,
            'user_id'         => $this->userId,
            'error'           => $exception->getMessage(),
        ]);
    }
}
