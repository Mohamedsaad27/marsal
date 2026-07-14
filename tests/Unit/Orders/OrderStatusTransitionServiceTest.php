<?php

namespace Tests\Unit\Orders;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Services\OrderStatusTransitionService;
use PHPUnit\Framework\TestCase;

class OrderStatusTransitionServiceTest extends TestCase
{
    public function test_retryable_agent_statuses_can_return_to_out_for_delivery(): void
    {
        $service = new OrderStatusTransitionService();

        foreach ($this->retryableStatuses() as $status) {
            $service->assertCanTransition($status, OrderStatusEnum::OutForDelivery);

            $this->assertContains('start_delivery', $service->availableActions($status));
        }
    }

    /**
     * @return list<OrderStatusEnum>
     */
    private function retryableStatuses(): array
    {
        return [
            OrderStatusEnum::PhoneOff,
            OrderStatusEnum::NoAnswer,
            OrderStatusEnum::WrongPhone,
            OrderStatusEnum::Postponed,
        ];
    }
}
