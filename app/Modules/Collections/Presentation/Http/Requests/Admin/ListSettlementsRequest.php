<?php

namespace App\Modules\Collections\Presentation\Http\Requests\Admin;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class ListSettlementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settlementTypes = implode(',', array_column(SettlementTypeEnum::cases(), 'value'));
        $statuses = implode(',', array_column(SettlementStatusEnum::cases(), 'value'));

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'settlement_type' => ['nullable', 'integer', "in:{$settlementTypes}"],
            'status' => ['nullable', 'integer', "in:{$statuses}"],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
