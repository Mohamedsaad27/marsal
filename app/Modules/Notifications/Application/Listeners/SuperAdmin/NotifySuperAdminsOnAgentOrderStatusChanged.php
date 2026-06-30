<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\AgentOrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnAgentOrderStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(AgentOrderStatusChanged $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::StatusChange,
            templateVars: [
                'order_code' => $event->orderCode,
                'agent_name' => $event->agentName,
                'status_label' => $event->statusLabelAr,
            ],
            data: ['order_id' => $event->orderId],
        );
    }
}
