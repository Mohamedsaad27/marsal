<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class SettlementNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.settlement_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
