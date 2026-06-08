<?php

namespace App\Modules\AuditLog\Presentation\Http\Requests;

use App\Modules\AuditLog\Domain\Enums\AuditDatePeriodEnum;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IndexAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validEvents  = implode(',', array_column(AuditEventEnum::cases(), 'value'));
        $validPeriods = implode(',', array_column(AuditDatePeriodEnum::cases(), 'value'));

        return [
            'user_id'        => ['nullable', 'uuid'],
            'event'          => ['nullable', 'integer', "in:{$validEvents}"],
            'auditable_type' => ['nullable', 'string', 'max:100'],
            'auditable_id'   => ['nullable', 'string', 'max:36'],
            'date_period'    => ['nullable', 'string', "in:{$validPeriods}"],
            'date_from'      => ['nullable', 'required_if:date_period,custom', 'date'],
            'date_to'        => ['nullable', 'required_if:date_period,custom', 'date', 'after_or_equal:date_from'],
            'per_page'       => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $period = $this->input('date_period');
            $from   = $this->input('date_from');
            $to     = $this->input('date_to');

            if ($period !== null) {
                return;
            }

            if (($from && ! $to) || (! $from && $to)) {
                $validator->errors()->add(
                    'date_to',
                    __('audit_logs::messages.date_range_both_required'),
                );
            }
        });
    }
}
