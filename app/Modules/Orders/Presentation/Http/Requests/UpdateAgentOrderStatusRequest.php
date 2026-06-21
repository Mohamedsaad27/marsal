<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use App\Modules\Orders\Application\UseCases\Agent\UploadDeliveryProofUseCase;
use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_column(OrderStatusEnum::cases(), 'value'));
        $validCollectionTypes = implode(',', array_column(CollectionTypeEnum::cases(), 'value'));
        $statusId = (int) $this->input('status_id');

        return [
            'status_id' => ['required', 'integer', "in:{$validStatuses}"],
            'notes' => ['nullable', 'string', 'max:500'],
            'collected_amount' => [
                Rule::requiredIf(in_array($statusId, [5, 6, 7, 8], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'collection_type' => [
                Rule::requiredIf(in_array($statusId, [5, 6, 7, 8], true)),
                'nullable',
                'integer',
                "in:{$validCollectionTypes}",
            ],
            'new_cod_amount' => [
                Rule::requiredIf($statusId === 6),
                'nullable',
                'numeric',
                'min:0',
            ],
            'postponed_date' => [
                Rule::requiredIf($statusId === 15),
                'nullable',
                'date',
                'after:today',
            ],
        ];
    }
}
