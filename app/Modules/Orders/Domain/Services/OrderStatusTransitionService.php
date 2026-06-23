<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

class OrderStatusTransitionService
{
    /** @return list<string> */
    public function availableActions(OrderStatusEnum $status): array
    {
        return match ($status) {
            OrderStatusEnum::Assigned => [
                'start_delivery',
                'postpone',
                'call_customer',
            ],
            OrderStatusEnum::OutForDelivery => [
                'confirm_delivery',
                'refuse',
                'no_answer',
                'phone_off',
                'postpone',
                'call_customer',
            ],
            OrderStatusEnum::AwaitingApproval => [
                'call_customer',
            ],
            default => [],
        };
    }

    public function assertCanTransition(OrderStatusEnum $from, OrderStatusEnum $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = match ($from) {
            OrderStatusEnum::Assigned => [
                OrderStatusEnum::OutForDelivery,
                OrderStatusEnum::Postponed,
            ],
            OrderStatusEnum::OutForDelivery => [
                OrderStatusEnum::Delivered,
                OrderStatusEnum::DeliveredPriceChanged,
                OrderStatusEnum::PartialDelivery,
                OrderStatusEnum::RefusedPaidShipping,
                OrderStatusEnum::RefusedNoPayment,
                OrderStatusEnum::CustomerCancelled,
                OrderStatusEnum::NoAnswer,
                OrderStatusEnum::PhoneOff,
                OrderStatusEnum::Postponed,
            ],
            default => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Transition from {$from->name} to {$to->name} is not allowed.",
            );
        }
    }

    /**
     * Price-change requests store awaiting_approval until reviewed.
     */
    public function resolveStoredStatus(OrderStatusEnum $requested): OrderStatusEnum
    {
        if ($requested === OrderStatusEnum::DeliveredPriceChanged) {
            return OrderStatusEnum::AwaitingApproval;
        }

        return $requested;
    }
}
