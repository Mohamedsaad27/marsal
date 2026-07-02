<?php

namespace App\Modules\Chat\Presentation\Http\Requests;

use App\Modules\Chat\Application\DTOs\SendMessageDTO;
use App\Modules\Chat\Domain\Enums\MessageTypeEnum;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class SendMessageRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'chat';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = (int) $this->input('message_type', MessageTypeEnum::Text->value);

        return [
            'message_type' => ['required', new Enum(MessageTypeEnum::class)],
            'body' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:message_type,' . MessageTypeEnum::Text->value,
            ],
            'attachment' => [
                'nullable',
                'file',
                'required_if:message_type,' . MessageTypeEnum::Image->value . ',' . MessageTypeEnum::Voice->value,
                $type === MessageTypeEnum::Image->value
                    ? File::image()->max(10240)
                    : File::types(['mp3', 'm4a', 'wav', 'ogg', 'aac', 'webm', 'mp4'])->max(20480),
            ],
        ];
    }

    public function toDTO(string $conversationId, string $senderUserId): SendMessageDTO
    {
        $messageType = MessageTypeEnum::from((int) $this->input('message_type'));

        return new SendMessageDTO(
            conversationId: $conversationId,
            senderUserId: $senderUserId,
            messageType: $messageType,
            body: $this->input('body'),
            attachment: $this->file('attachment'),
        );
    }
}
