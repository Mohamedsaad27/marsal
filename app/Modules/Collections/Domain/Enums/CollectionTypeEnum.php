<?php

namespace App\Modules\Collections\Domain\Enums;

enum CollectionTypeEnum: int
{
    case Cod = 1;
    case ShippingFee = 2;
    case Partial = 3;

    public function labelAr(): string
    {
        return match ($this) {
            self::Cod => 'مبلغ الاستلام (COD)',
            self::ShippingFee => 'رسوم الشحن',
            self::Partial => 'تحصيل جزئي',
        };
    }
}
