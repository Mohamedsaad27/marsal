<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Services\NotificationTemplateService;
use App\Modules\Notifications\Domain\Services\SuperAdminRecipientResolver;

class NotifySuperAdminsUseCase
{
    public function __construct(
        private readonly SuperAdminRecipientResolver $recipients,
        private readonly NotificationTemplateService $templates,
        private readonly SendNotificationUseCase $sendNotification,
    ) {}

    /**
     * @param  array<string, string>  $templateVars
     * @param  array<string, mixed>   $data
     */
    public function execute(
        NotificationTypeEnum $type,
        array $templateVars = [],
        array $data = [],
        bool $sendViaFcm = true,
    ): void {
        $message = $this->templates->buildForSuperAdmin($type, $templateVars);

        foreach ($this->recipients->activeUserIds() as $userId) {
            $this->sendNotification->execute(
                SendNotificationDTO::fromMessage(
                    userId: $userId,
                    type: $type,
                    message: $message,
                    data: $data,
                    sendViaFcm: $sendViaFcm,
                ),
            );
        }
    }
}
