<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\NewMessageSent;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleNewMessageSent implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(NewMessageSent $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::NewMessage,
            [
                'sender_name' => $event->senderName,
                'order_code'  => $event->orderCode,
            ],
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->recipientUserId,
                type:       NotificationTypeEnum::NewMessage,
                message:    $message,
                data:       [
                    'order_id'        => $event->orderId,
                    'conversation_id' => $event->conversationId,
                ],
                sendViaFcm: true,
            )
        );
    }
}
