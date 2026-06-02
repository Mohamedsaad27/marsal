<?php

namespace App\Modules\Locations\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class CityNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('locations::messages.city_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
