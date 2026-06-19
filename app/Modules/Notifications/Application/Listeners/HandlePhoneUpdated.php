<?php

namespace App\Modules\Notifications\Application\Listeners;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\PhoneUpdated;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePhoneUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(
        private NotificationTemplateService $templateService,
        private SendNotificationUseCase     $sendNotification,
    ) {}

    public function handle(PhoneUpdated $event): void
    {
        // PhoneUpdated uses a fully static Arabic message — no dynamic variables
        $message = $this->templateService->build(
            NotificationTypeEnum::PhoneUpdated,
        );

        $this->sendNotification->execute(
            SendNotificationDTO::fromMessage(
                userId:     $event->agentUserId,
                type:       NotificationTypeEnum::PhoneUpdated,
                message:    $message,
                data:       [],
                sendViaFcm: true,
            )
        );
    }
}
