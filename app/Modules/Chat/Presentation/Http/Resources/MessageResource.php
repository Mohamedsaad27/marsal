<?php

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Domain\Enums\MessageTypeEnum;
use App\Modules\Chat\Infrastructure\Database\Models\Message;
use App\Modules\Core\Presentation\Http\Resources\MediaFileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->message_id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender?->user_id,
                'name' => $this->sender?->name,
            ]),
            'body' => $this->body,
            'type' => [
                'code' => $this->message_type instanceof MessageTypeEnum
                    ? $this->message_type->value
                    : (int) $this->message_type,
                'label' => $this->message_type instanceof MessageTypeEnum
                    ? $this->message_type->labelAr()
                    : null,
            ],
            'attachment' => $this->when(
                $this->relationLoaded('mediaFiles') && $this->mediaFiles->isNotEmpty(),
                fn () => new MediaFileResource($this->mediaFiles->first()),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
