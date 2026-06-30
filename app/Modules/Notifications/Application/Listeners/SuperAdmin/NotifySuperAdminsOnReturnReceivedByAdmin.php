<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\ReturnReceivedByAdmin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnReturnReceivedByAdmin implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(ReturnReceivedByAdmin $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::Returned,
            templateVars: [
                'return_action' => 'تم استلام مرتجع من المندوب',
                'order_code' => $event->orderCode,
                'agent_name' => $event->agentName,
            ],
            data: [
                'return_id' => $event->returnId,
                'order_id' => $event->orderId,
            ],
        );
    }
}
