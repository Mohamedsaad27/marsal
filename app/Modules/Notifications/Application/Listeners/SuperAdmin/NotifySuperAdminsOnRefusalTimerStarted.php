<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\RefusalTimerStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnRefusalTimerStarted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(RefusalTimerStarted $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::TimerStart,
            templateVars: [
                'agent_name' => $event->agentName,
                'order_code' => $event->orderCode,
                'minutes' => (string) $event->timerMinutes,
            ],
            data: ['order_id' => $event->orderId],
        );
    }
}
