<?php

namespace App\Modules\Notifications\Domain\DTOs;

/**
 * Immutable Domain value object returned by NotificationTemplateService::build().
 * Contains ready-to-store/send Arabic notification text.
 */
readonly class NotificationMessageDTO
{
    public function __construct(
        public string $titleAr,
        public string $bodyAr,
    ) {}
}
