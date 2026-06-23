<?php

namespace App\Modules\Returns\Domain\Enums;

enum ReturnStatusEnum: int
{
    case Pending         = 1;
    case ReceivedByAdmin = 2;
    case SentToCompany   = 3;

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending         => 'بانتظار الاستلام',
            self::ReceivedByAdmin => 'تم الاستلام من المندوب',
            self::SentToCompany   => 'تم التسليم للشركة',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending         => 'orange',
            self::ReceivedByAdmin => 'blue',
            self::SentToCompany   => 'green',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::SentToCompany;
    }
}
