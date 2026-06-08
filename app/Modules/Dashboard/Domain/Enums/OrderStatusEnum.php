<?php

namespace App\Modules\Dashboard\Domain\Enums;

enum OrderStatusEnum: int
{
    case Pending = 1;
    case InDelivery = 2;
    case Delivered = 3;
    case Postponed = 4;
    case Failed = 5;
    case Rejected = 6;

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => __('dashboard::dashboard.status.pending'),
            self::InDelivery => __('dashboard::dashboard.status.in_delivery'),
            self::Delivered => __('dashboard::dashboard.status.delivered'),
            self::Postponed => __('dashboard::dashboard.status.postponed'),
            self::Failed => __('dashboard::dashboard.status.failed'),
            self::Rejected => __('dashboard::dashboard.status.rejected'),
        };
    }

    public static function tryFromStatus(int $status): ?self
    {
        return self::tryFrom($status);
    }
}
