<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\ApprovalRequested;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleApprovalRequested implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(ApprovalRequested $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::ApprovalRequest,
            [
                'agent_name' => $event->agentName,
                'order_code' => $event->orderCode,
                'new_amount' => $event->newAmount,
            ],
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->companyUserId,
                type:       NotificationTypeEnum::ApprovalRequest,
                message:    $message,
                data:       ['order_id' => $event->orderId],
                sendViaFcm: true,
            )
        );
    }
}
