<?php

namespace App\Modules\Orders\Application\DTOs;

use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use Illuminate\Http\UploadedFile;

readonly class UploadOrderProofDTO
{
    public function __construct(
        public string $orderId,
        public string $deliveryAgentId,
        public string $userId,
        public UploadedFile $photo,
        public OrderProofFileTypeEnum $fileType,
    ) {}
}
