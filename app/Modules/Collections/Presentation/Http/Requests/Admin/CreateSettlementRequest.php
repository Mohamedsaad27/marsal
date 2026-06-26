<?php

namespace App\Modules\Collections\Presentation\Http\Requests\Admin;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class CreateSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settlementTypes = implode(',', array_column(SettlementTypeEnum::cases(), 'value'));

        return [
            'settlement_type' => ['required', 'integer', "in:{$settlementTypes}"],
            'reference_entity_id' => ['required', 'uuid'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = (int) $this->input('settlement_type');
            $entityId = $this->input('reference_entity_id');

            $exists = match ($type) {
                SettlementTypeEnum::Agent->value => DB::table('delivery_agents')
                    ->where('delivery_agent_id', $entityId)
                    ->whereNull('deleted_at')
                    ->exists(),
                SettlementTypeEnum::Company->value => DB::table('shipping_companies')
                    ->where('shipping_company_id', $entityId)
                    ->whereNull('deleted_at')
                    ->exists(),
                default => false,
            };

            if (! $exists) {
                $validator->errors()->add('reference_entity_id', __('validation.exists', ['attribute' => 'reference_entity_id']));
            }
        });
    }
}
