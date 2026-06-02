<?php

namespace App\Modules\Locations\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class GovernorateNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('locations::messages.governorate_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
