<?php

namespace App\Modules\Notifications\Domain\Enums;

enum NotificationKpiCategoryEnum: string
{
    case Approvals   = 'approvals';
    case Collections = 'collections';
    case Shipments   = 'shipments';

    public function labelAr(): string
    {
        return match ($this) {
            self::Approvals   => 'موافقات',
            self::Collections => 'تحصيلات',
            self::Shipments   => 'شحنات',
        };
    }

    /**
     * @return int[]
     */
    public function notificationTypeValues(): array
    {
        return array_values(array_map(
            fn (NotificationTypeEnum $type) => $type->value,
            array_filter(
                NotificationTypeEnum::cases(),
                fn (NotificationTypeEnum $type) => $type->kpiCategory() === $this,
            ),
        ));
    }
}
