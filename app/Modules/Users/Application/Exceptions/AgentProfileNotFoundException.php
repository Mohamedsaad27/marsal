<?php

namespace App\Modules\Users\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class AgentProfileNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'لم يتم العثور على ملف المندوب';
    }

    protected function getDefaultStatusCode(): int
    {
        return 403;
    }
}
