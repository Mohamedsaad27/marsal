<?php

namespace App\Modules\Locations\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class LocationInUseException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('locations::messages.location_in_use');
    }

    protected function getDefaultStatusCode(): int
    {
        return 409;
    }
}
