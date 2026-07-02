<?php

namespace App\Modules\Chat\Application\DTOs;

use App\Modules\Chat\Domain\Enums\MessageTypeEnum;
use Illuminate\Http\UploadedFile;

readonly class SendMessageDTO
{
    public function __construct(
        public string $conversationId,
        public string $senderUserId,
        public MessageTypeEnum $messageType,
        public ?string $body = null,
        public ?UploadedFile $attachment = null,
    ) {}
}
