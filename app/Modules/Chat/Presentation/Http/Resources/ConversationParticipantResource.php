<?php

namespace App\Modules\Chat\Presentation\Http\Resources;

use App\Modules\Chat\Infrastructure\Database\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConversationParticipant */
class ConversationParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'last_read_at' => $this->last_read_at?->toIso8601String(),
        ];
    }
}
