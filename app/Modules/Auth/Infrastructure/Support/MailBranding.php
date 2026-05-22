<?php

namespace App\Modules\Auth\Infrastructure\Support;

class MailBranding
{
    public static function logoUrl(): string
    {
        $path = ltrim((string) config('auth_module.logo_path', 'images/logo.png'), '/');

        return rtrim((string) config('app.url'), '/') . '/' . $path;
    }

    public static function loginUrl(): string
    {
        return (string) config('auth_module.login_url', config('app.url'));
    }

    public static function appName(): string
    {
        return (string) config('auth_module.mail_app_name', 'شركة مرسال للشحن والتوصيل');
    }

    public static function emailSubject(string $suffixKey): string
    {
        return self::appName() . ' — ' . __($suffixKey);
    }
}
