<?php

namespace App\Modules\Notifications\Application\DTOs;

use App\Modules\Notifications\Domain\DTOs\NotificationMessageDTO;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;

/**
 * Input DTO for SendNotificationUseCase.
 * Constructed by each event listener after obtaining the message from NotificationTemplateService.
 */
readonly class SendNotificationDTO
{
    public function __construct(
        public string               $userId,
        public NotificationTypeEnum $notificationType,
        public string               $titleAr,
        public string               $bodyAr,
        public array                $data        = [],
        public bool                 $sendViaFcm  = true,
    ) {}

    public static function fromMessage(
        string               $userId,
        NotificationTypeEnum $type,
        NotificationMessageDTO $message,
        array                $data       = [],
        bool                 $sendViaFcm = true,
    ): self {
        return new self(
            userId:           $userId,
            notificationType: $type,
            titleAr:          $message->titleAr,
            bodyAr:           $message->bodyAr,
            data:             $data,
            sendViaFcm:       $sendViaFcm,
        );
    }
}
