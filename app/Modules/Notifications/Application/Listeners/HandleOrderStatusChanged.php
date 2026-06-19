<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\OrderStatusChanged;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleOrderStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::StatusChange,
            [
                'order_code'   => $event->orderCode,
                'status_label' => $event->statusLabelAr,
            ],
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->companyUserId,
                type:       NotificationTypeEnum::StatusChange,
                message:    $message,
                data:       ['order_id' => $event->orderId],
                sendViaFcm: true,
            )
        );
    }
}
