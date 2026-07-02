<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_company_id' => ['nullable', 'string', 'uuid', 'exists:shipping_companies,shipping_company_id'],
            'delivery_agent_id'   => ['nullable', 'string', 'uuid', 'exists:delivery_agents,delivery_agent_id'],
            'status'              => ['nullable', 'string', 'max:100'],
            'date_from'           => ['nullable', 'date_format:Y-m-d'],
            'date_to'             => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = $this->input('status');

            if ($status === null || $status === '') {
                return;
            }

            $validIds = array_map(
                static fn (OrderStatusEnum $status) => (string) $status->value,
                OrderStatusEnum::cases(),
            );

            foreach (explode(',', (string) $status) as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                if (! ctype_digit($part) || ! in_array($part, $validIds, true)) {
                    $validator->errors()->add('status', __('orders::messages.export_invalid_status'));

                    return;
                }
            }
        });
    }
}
