<?php

namespace App\Modules\Reports\Presentation\Http\Requests;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_unique(array_merge(
            array_column(OrderStatusEnum::cases(), 'value'),
            array_column(SettlementStatusEnum::cases(), 'value'),
        ));

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'integer', 'in:' . implode(',', $statuses)],
            'collection_type' => ['nullable', 'integer', 'in:' . implode(',', array_column(CollectionTypeEnum::cases(), 'value'))],
            'settlement_type' => ['nullable', 'integer', 'in:' . implode(',', array_column(SettlementTypeEnum::cases(), 'value'))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'agent_id' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'uuid'],
            'governorate_id' => ['nullable', 'uuid'],
            'is_active' => ['nullable', 'integer', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
