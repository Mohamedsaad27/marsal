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

    public function test_locked_agent_statuses_cannot_be_updated_by_agent(): void
    {
        $service = new OrderStatusTransitionService();

        foreach ([OrderStatusEnum::UnsafeArea, OrderStatusEnum::OutsideGovernorate] as $status) {
            $this->assertSame([], $service->availableActions($status));

            foreach ([$status, OrderStatusEnum::OutForDelivery] as $targetStatus) {
                try {
                    $service->assertCanTransition($status, $targetStatus);
                    $this->fail("Expected {$status->name} to reject agent transition.");
                } catch (\InvalidArgumentException $exception) {
                    $this->assertStringContainsString('locked for delivery agents', $exception->getMessage());
                }
            }
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
