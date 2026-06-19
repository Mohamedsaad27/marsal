<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notifications\Infrastructure\Jobs\SendFcmNotificationJob;

class SendNotificationUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Persist the notification record and dispatch the FCM job if needed.
     *
     * @return array The persisted notification as a plain array.
     */
    public function execute(SendNotificationDTO $dto): array
    {
        $notification = $this->repository->create([
            'user_id'           => $dto->userId,
            'notification_type' => $dto->notificationType->value,
            'title_ar'          => $dto->titleAr,
            'body_ar'           => $dto->bodyAr,
            'data'              => $dto->data,
            'is_read'           => 0,
            'sent_via_fcm'      => false,
        ]);

        if ($dto->sendViaFcm) {
            SendFcmNotificationJob::dispatch(
                notificationId: $notification['notification_id'],
                userId:         $dto->userId,
                titleAr:        $dto->titleAr,
                bodyAr:         $dto->bodyAr,
                data:           array_merge($dto->data, [
                    'type' => (string) $dto->notificationType->value,
                ]),
            )->onQueue('notifications');
        }

        return $notification;
    }
}
