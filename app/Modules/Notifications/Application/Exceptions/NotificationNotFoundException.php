<?php

namespace App\Modules\Notifications\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class NotificationNotFoundException extends BaseException
{
    private string $notificationId = '';

    public function __construct(string $notificationId = '')
    {
        $this->notificationId = $notificationId;
        parent::__construct();
    }

    protected function getDefaultMessage(): string
    {
        return $this->notificationId
            ? "الإشعار رقم [{$this->notificationId}] غير موجود أو لا يخصك"
            : 'الإشعار غير موجود أو لا يخصك';
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
