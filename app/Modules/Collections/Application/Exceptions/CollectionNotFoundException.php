<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class CollectionNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.collection_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
