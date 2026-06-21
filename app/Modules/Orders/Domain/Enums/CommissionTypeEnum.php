<?php

namespace App\Modules\Orders\Domain\Enums;

enum CommissionTypeEnum: int
{
    case Percentage = 1;
    case Fixed = 2;
}
