<?php

namespace App\Modules\Collections\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class CollectionAlreadyReceivedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('collections::messages.collection_already_received');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
