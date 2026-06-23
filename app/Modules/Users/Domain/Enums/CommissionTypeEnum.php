<?php

namespace App\Modules\Users\Domain\Enums;

enum CommissionTypeEnum: int
{
    case Percentage = 1;
    case Fixed = 2;

    public function labelAr(): string
    {
        return match ($this) {
            self::Percentage => 'نسبة مئوية',
            self::Fixed => 'مبلغ ثابت',
        };
    }
}
