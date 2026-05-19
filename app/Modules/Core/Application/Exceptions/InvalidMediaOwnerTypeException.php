<?php

namespace App\Modules\Core\Application\Exceptions;

class InvalidMediaOwnerTypeException extends BaseException
{
    public function __construct(string $ownerType)
    {
        parent::__construct(
            __('core::messages.invalid_media_owner_type', ['type' => $ownerType]),
            422
        );
    }

    protected function getDefaultMessage(): string
    {
        return __('core::messages.invalid_media_owner_type', ['type' => 'unknown']);
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
