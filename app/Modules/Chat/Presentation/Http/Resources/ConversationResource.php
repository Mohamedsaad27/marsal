<?php

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Enums\ConversationTypeEnum;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->whenLoaded('order', fn () => $this->order);

        return [
            'id' => $this->conversation_id,
            'order_id' => $this->order_id,
            'order' => $this->when($order, function () use ($order) {
                return [
                    'id' => $order->order_id,
                    'reference_code' => $order->reference_code,
                    'reference_no' => $order->reference_no,
                    'status' => $order->status?->value,
                    'company_name' => $order->shippingCompany?->company_name,
                    'agent_name' => $order->deliveryAgent?->user?->name,
                ];
            }),
            'type' => [
                'code' => $this->conversation_type instanceof ConversationTypeEnum
                    ? $this->conversation_type->value
                    : (int) $this->conversation_type,
                'label' => $this->conversation_type instanceof ConversationTypeEnum
                    ? $this->conversation_type->labelAr()
                    : null,
            ],
            'participants' => ConversationParticipantResource::collection(
                $this->whenLoaded('participants'),
            ),
            'messages_count' => $this->whenCounted('messages'),
            'last_message_at' => $this->messages_max_created_at
                ? \Illuminate\Support\Carbon::parse($this->messages_max_created_at)->toIso8601String()
                : $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
