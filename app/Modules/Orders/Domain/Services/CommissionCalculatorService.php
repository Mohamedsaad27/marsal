<?php

namespace App\Modules\Orders\Domain\Services;

class CommissionCalculatorService
{
    public function calculate(float $collectedAmount, float $commissionValue): array
    {
        $commissionAmount = round($commissionValue, 2);

        return [
            'commission_amount' => $commissionAmount,
            'net_due' => round($collectedAmount - $commissionAmount, 2),
        ];
    }
}
