<?php

namespace App\Modules\Orders\Domain\Enums;

enum ApprovalTypeEnum: int
{
    case PriceChange = 1;
    case ShippingFee = 2;
    case PartialAmount = 3;
}
