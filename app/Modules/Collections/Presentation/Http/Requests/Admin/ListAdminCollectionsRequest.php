<?php

namespace App\Modules\Collections\Presentation\Http\Requests\Admin;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminCollectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $collectionTypes = implode(',', array_column(CollectionTypeEnum::cases(), 'value'));

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'collection_type' => ['nullable', 'integer', "in:{$collectionTypes}"],
            'status' => ['nullable', 'string', Rule::in(['pending_cash', 'unsettled', 'settled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'agent_id' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
