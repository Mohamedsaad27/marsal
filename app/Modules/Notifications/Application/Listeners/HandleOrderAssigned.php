<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\OrderAssigned;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleOrderAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(OrderAssigned $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::NewOrder,
            ['order_code' => $event->orderCode],
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->agentUserId,
                type:       NotificationTypeEnum::NewOrder,
                message:    $message,
                data:       ['order_id' => $event->orderId],
                sendViaFcm: true,
            )
        );
    }
}
