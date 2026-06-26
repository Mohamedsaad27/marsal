<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class NoCollectionsFoundForPeriodException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.no_collections_found_for_period');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
