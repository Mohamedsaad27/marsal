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
                'unsafe_area',
                'outside_governorate',
                'wrong_phone',
                'postpone',
                'call_customer',
            ],
            OrderStatusEnum::AwaitingApproval => [
                'call_customer',
            ],
            OrderStatusEnum::NoAnswer,
            OrderStatusEnum::PhoneOff,
            OrderStatusEnum::WrongPhone,
            OrderStatusEnum::Postponed => [
                'start_delivery',
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
                OrderStatusEnum::UnsafeArea,
                OrderStatusEnum::OutsideGovernorate,
                OrderStatusEnum::WrongPhone,
                OrderStatusEnum::Postponed,
            ],
            OrderStatusEnum::NoAnswer,
            OrderStatusEnum::PhoneOff,
            OrderStatusEnum::WrongPhone,
            OrderStatusEnum::Postponed => [
                OrderStatusEnum::OutForDelivery,
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
     * Admin can override the agent transition graph and set any valid status.
     */
    public function assertAdminCanTransition(OrderStatusEnum $from, OrderStatusEnum $to): void
    {
        if ($from === $to) {
            return;
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
