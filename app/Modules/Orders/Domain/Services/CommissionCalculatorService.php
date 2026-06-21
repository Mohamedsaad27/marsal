<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Orders\Domain\Enums\CommissionTypeEnum;

class CommissionCalculatorService
{
    public function calculate(float $collectedAmount, int $commissionType, float $commissionValue): array
    {
        $type = CommissionTypeEnum::tryFrom($commissionType) ?? CommissionTypeEnum::Percentage;

        $commissionAmount = match ($type) {
            CommissionTypeEnum::Percentage => round($collectedAmount * ($commissionValue / 100), 2),
            CommissionTypeEnum::Fixed => round($commissionValue, 2),
        };

        return [
            'commission_amount' => $commissionAmount,
            'net_due' => round($collectedAmount - $commissionAmount, 2),
        ];
    }
}
