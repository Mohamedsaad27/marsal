<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class SettlementInvalidStatusTransitionException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.settlement_invalid_status_transition');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
