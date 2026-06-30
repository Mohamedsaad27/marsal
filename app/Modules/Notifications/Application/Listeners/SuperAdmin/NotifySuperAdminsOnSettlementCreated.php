<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\SettlementCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnSettlementCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(SettlementCreated $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::Settled,
            templateVars: [
                'settlement_action' => 'تم إنشاء تسوية جديدة',
                'entity_label' => $event->entityLabel,
                'net_amount' => $event->netAmount,
            ],
            data: ['settlement_id' => $event->settlementId],
        );
    }
}
