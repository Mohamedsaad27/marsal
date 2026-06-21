<?php

namespace App\Modules\Orders\Domain\Enums;

enum ApprovalStatusEnum: int
{
    case Pending = 1;
    case Approved = 2;
    case Rejected = 3;
    case Expired = 4;
}
