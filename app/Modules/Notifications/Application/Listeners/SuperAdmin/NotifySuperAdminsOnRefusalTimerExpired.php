<?php

namespace App\Modules\Notifications\Application\Listeners\SuperAdmin;

use App\Modules\Notifications\Application\UseCases\NotifySuperAdminsUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\RefusalTimerExpired;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySuperAdminsOnRefusalTimerExpired implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private readonly NotifySuperAdminsUseCase $notifySuperAdmins,
    ) {}

    public function handle(RefusalTimerExpired $event): void
    {
        $this->notifySuperAdmins->execute(
            type: NotificationTypeEnum::TimerExpired,
            templateVars: ['order_code' => $event->orderCode],
            data: ['order_id' => $event->orderId],
        );
    }
}
