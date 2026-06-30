<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\CollectionRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnCollectionRecorded implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(CollectionRecorded $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::Collected,
            templateVars: [
                'order_code' => $event->orderCode,
                'agent_name' => $event->agentName,
                'collected_amount' => $event->collectedAmount,
            ],
            data: [
                'order_id' => $event->orderId,
                'collection_id' => $event->collectionId,
            ],
        );
    }
}
