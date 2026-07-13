<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\CollectionCashReceived;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleCollectionCashReceived implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase $sendNotification,
    ) {}

    public function handle(CollectionCashReceived $event): void
    {
        $message = $this->templateService->build(
            NotificationTypeEnum::Collected,
            [
                'order_code' => $event->orderCode,
                'agent_name' => $event->agentName,
                'collected_amount' => $event->collectedAmount,
            ],
        );

        $data = [
            'collection_id' => $event->collectionId,
            'order_id' => $event->orderId,
        ];

        foreach (array_filter([$event->agentUserId, $event->companyUserId]) as $userId) {
            $this->sendNotification->execute(
                SendNotificationDTO::fromMessage(
                    userId: $userId,
                    type: NotificationTypeEnum::Collected,
                    message: $message,
                    data: $data,
                    sendViaFcm: true,
                )
            );
        }
    }
}
