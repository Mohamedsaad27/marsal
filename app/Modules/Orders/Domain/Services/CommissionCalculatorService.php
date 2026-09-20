<?php

namespace App\Modules\Orders\Domain\Services;

class CommissionCalculatorService
{
    /**
     * @return array{
     *     agent_commission_amount: float,
     *     agent_net_due: float,
     *     system_commission_amount: float,
     *     company_net_due: float
     * }
     */
    public function calculateForCollection(
        float $collectedAmount,
        float $agentCommissionValue,
        float $systemCommissionValue,
    ): array {
        $collectedAmount = round($collectedAmount, 2);
        $agentCommissionAmount = round($agentCommissionValue, 2);
        $systemCommissionAmount = round($systemCommissionValue, 2);

        return [
            'agent_commission_amount' => $agentCommissionAmount,
            'agent_net_due' => round($collectedAmount - $agentCommissionAmount, 2),
            'system_commission_amount' => $systemCommissionAmount,
            'company_net_due' => round($collectedAmount - $systemCommissionAmount, 2),
        ];
    }
}
