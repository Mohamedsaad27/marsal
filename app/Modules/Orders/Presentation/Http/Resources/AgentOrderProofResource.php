<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOrderProofResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileType = OrderProofFileTypeEnum::from((int) $this->resource['file_type']);

        return [
            'id' => $this->resource['proof_id'],
            'proof_id' => $this->resource['proof_id'],
            'file_url' => $this->resource['file_url'],
            'file_type' => [
                'code' => $fileType->value,
                'label' => match ($fileType) {
                    OrderProofFileTypeEnum::Image => 'image',
                    OrderProofFileTypeEnum::Pdf => 'pdf',
                    OrderProofFileTypeEnum::Other => 'other',
                },
            ],
        ];
    }
}
