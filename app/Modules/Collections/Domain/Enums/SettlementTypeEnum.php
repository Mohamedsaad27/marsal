<?php

namespace App\Modules\Collections\Domain\Enums;

enum SettlementTypeEnum: int
{
    case Agent = 1;
    case Company = 2;

    public function labelAr(): string
    {
        return match ($this) {
            self::Agent => 'مندوب توصيل',
            self::Company => 'شركة شحن',
        };
    }
}
