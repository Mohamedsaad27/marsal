<?php

namespace Tests\Unit\Orders;

use App\Modules\Orders\Domain\Services\CommissionCalculatorService;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorServiceTest extends TestCase
{
    public function test_it_calculates_independent_agent_and_system_commissions(): void
    {
        $result = (new CommissionCalculatorService)->calculateForCollection(
            collectedAmount: 1000,
            agentCommissionValue: 50,
            systemCommissionValue: 80,
        );

        $this->assertSame([
            'agent_commission_amount' => 50.0,
            'agent_net_due' => 950.0,
            'system_commission_amount' => 80.0,
            'company_net_due' => 920.0,
        ], $result);
    }

    public function test_it_preserves_negative_agent_and_company_net_dues(): void
    {
        $result = (new CommissionCalculatorService)->calculateForCollection(
            collectedAmount: 30,
            agentCommissionValue: 50,
            systemCommissionValue: 50,
        );

        $this->assertSame(-20.0, $result['agent_net_due']);
        $this->assertSame(-20.0, $result['company_net_due']);
    }
}
