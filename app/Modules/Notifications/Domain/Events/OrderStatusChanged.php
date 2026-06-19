<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when an order's status changes.
 * Recipient: shipping company user.
 */
readonly class OrderStatusChanged
{
    public function __construct(
        public string $companyUserId,
        public string $orderCode,
        public string $orderId,
        public string $statusLabelAr,
    ) {}
}
