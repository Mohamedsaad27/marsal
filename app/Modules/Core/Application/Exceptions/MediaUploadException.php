<?php

namespace App\Modules\Core\Application\Exceptions;

class MediaUploadException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('core::messages.media_upload_failed'), 422);
    }

    protected function getDefaultMessage(): string
    {
        return __('core::messages.media_upload_failed');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
