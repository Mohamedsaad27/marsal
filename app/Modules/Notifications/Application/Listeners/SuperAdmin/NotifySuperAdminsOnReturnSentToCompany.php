<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\ReturnSentToCompany;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnReturnSentToCompany implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(ReturnSentToCompany $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::Returned,
            templateVars: [
                'return_action' => 'تم إرسال مرتجع لشركة الشحن '.$event->companyName,
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
