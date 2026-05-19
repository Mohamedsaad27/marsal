<?php

namespace App\Modules\Core\Application\Exceptions;

class TenantNotResolvedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('core::messages.tenant_not_resolved');
    }

    protected function getDefaultStatusCode(): int
    {
        return 403;
    }
}
