<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\OrderReassigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnOrderReassigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(OrderReassigned $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::OrderReassigned,
            templateVars: [
                'order_code' => $event->orderCode,
                'old_agent' => $event->previousAgentName,
                'new_agent' => $event->newAgentName,
            ],
            data: ['order_id' => $event->orderId],
        );
    }
}
