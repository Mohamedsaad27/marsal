<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\RefusalTimerExpired;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends two separate notifications on timer expiry:
 *   1. To the shipping company user
 *   2. To the delivery agent user
 *
 * Both use the same Arabic message template — the template service is called once
 * and the resulting DTO is reused for both recipients.
 */
class HandleRefusalTimerExpired implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(RefusalTimerExpired $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::TimerExpired,
            ['order_code' => $event->orderCode],
        );

        $data = ['order_id' => $event->orderId];

        // Notify shipping company
        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->companyUserId,
                type:       NotificationTypeEnum::TimerExpired,
                message:    $message,
                data:       $data,
                sendViaFcm: true,
            )
        );

        // Notify delivery agent
        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->agentUserId,
                type:       NotificationTypeEnum::TimerExpired,
                message:    $message,
                data:       $data,
                sendViaFcm: true,
            )
        );
    }
}
