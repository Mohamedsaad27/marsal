<?php

namespace App\Modules\Users\Domain\Enums;

enum CommissionTypeEnum: int
{
    case Fixed = 2;

    public function labelAr(): string
    {
        return 'مبلغ ثابت';
    }
}
