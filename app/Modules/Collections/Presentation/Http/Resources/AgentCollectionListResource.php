<?php

namespace App\Modules\Collections\Presentation\Http\Resources;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Collection */
class AgentCollectionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $collectionType = $this->collection_type instanceof CollectionTypeEnum
            ? $this->collection_type
            : CollectionTypeEnum::tryFrom((int) $this->collection_type);

        return [
            'id' => $this->collection_id,
            'collection_id' => $this->collection_id,
            'order_id' => $this->order_id,
            'internal_code' => $this->whenLoaded(
                'order',
                fn () => $this->order?->reference_code,
            ),
            'customer_name' => $this->whenLoaded(
                'order',
                fn () => $this->order?->customerInfo?->customer_name,
            ),
            'collection_type' => [
                'id' => $collectionType?->value,
                'label' => $collectionType?->labelAr(),
            ],
            'collected_amount' => (float) $this->collected_amount,
            'is_settled' => $this->settlement_id !== null,
            'settlement_id' => $this->settlement_id,
            'collected_at' => $this->collected_at?->toISOString(),
        ];
    }
}
