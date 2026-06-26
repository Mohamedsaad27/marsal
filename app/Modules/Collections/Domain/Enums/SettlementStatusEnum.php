<?php

namespace App\Modules\Collections\Domain\Enums;

enum SettlementStatusEnum: int
{
    case Draft = 1;
    case Approved = 2;
    case Paid = 3;

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Approved => 'معتمدة',
            self::Paid => 'مدفوعة',
        };
    }
}
