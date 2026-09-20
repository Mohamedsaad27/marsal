<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class SettlementItemsMismatchException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.settlement_items_mismatch');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
