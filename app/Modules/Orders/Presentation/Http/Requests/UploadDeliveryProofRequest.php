<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class UploadDeliveryProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTypes = implode(',', array_column(OrderProofFileTypeEnum::cases(), 'value'));

        return [
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:' . UploadDeliveryProofUseCase::maxSizeKb(),
            ],
            'file_type' => ['required', 'integer', "in:{$validTypes}"],
        ];
    }
}
