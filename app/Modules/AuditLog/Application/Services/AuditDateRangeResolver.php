<?php

namespace App\Modules\AuditLog\Application\Services;

use App\Modules\AuditLog\Domain\Enums\AuditDatePeriodEnum;
use Carbon\Carbon;

class AuditDateRangeResolver
{
    /**
     * @return array{date_from: ?string, date_to: ?string}
     */
    public function resolve(?string $period, ?string $dateFrom, ?string $dateTo): array
    {
        if ($period === null && $dateFrom === null && $dateTo === null) {
            return ['date_from' => null, 'date_to' => null];
        }

        if ($period === null) {
            $period = AuditDatePeriodEnum::Custom->value;
        }

        $resolved = AuditDatePeriodEnum::from($period);

        return match ($resolved) {
            AuditDatePeriodEnum::Today => [
                'date_from' => now()->startOfDay()->toDateTimeString(),
                'date_to'   => now()->endOfDay()->toDateTimeString(),
            ],
            AuditDatePeriodEnum::ThisWeek => [
                'date_from' => now()->startOfWeek()->startOfDay()->toDateTimeString(),
                'date_to'   => now()->endOfWeek()->endOfDay()->toDateTimeString(),
            ],
            AuditDatePeriodEnum::ThisMonth => [
                'date_from' => now()->startOfMonth()->startOfDay()->toDateTimeString(),
                'date_to'   => now()->endOfMonth()->endOfDay()->toDateTimeString(),
            ],
            AuditDatePeriodEnum::Custom => [
                'date_from' => Carbon::parse($dateFrom)->startOfDay()->toDateTimeString(),
                'date_to'   => Carbon::parse($dateTo)->endOfDay()->toDateTimeString(),
            ],
        };
    }
}
