<?php

namespace App\Modules\Collections\Domain\Enums;

enum SettlementStatusEnum: int
{
    case Draft = 1;
    case Approved = 2;
    case Paid = 3;
}
