<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\ApprovalRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnApprovalRequested implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(ApprovalRequested $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::ApprovalRequest,
            templateVars: [
                'agent_name' => $event->agentName,
                'order_code' => $event->orderCode,
                'new_amount' => $event->newAmount,
            ],
            data: ['order_id' => $event->orderId],
        );
    }
}
