<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Infrastructure\Support\MailBranding;
use App\Modules\Users\Infrastructure\Database\Models\User;

class WhatsAppService
{
    /**
     * Build a click-to-chat wa.me link (no API). Opens WhatsApp to message the user's phone with a pre-filled welcome text.
     */
    public function buildWelcomeLink(User $user, string $plainPassword): string
    {
        $phone = $this->normalizePhoneForWaMe($user->phone);
        $message = $this->buildWelcomeMessage($user, $plainPassword);

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    public function buildWelcomeMessage(User $user, string $plainPassword): string
    {
        $appName = MailBranding::appName();
        $loginUrl = MailBranding::loginUrl();
        $supportPhone = (string) config('auth_module.platform_phone', '+201098001021');

        return __('auth::messages.whatsapp_welcome_body', [
            'name' => $user->name,
            'app' => $appName,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => $plainPassword,
            'login_url' => $loginUrl,
            'support_phone' => $supportPhone,
        ]);
    }

    /**
     * Egyptian numbers: 010... → 2010... (wa.me expects country code without +).
     */
    public function normalizePhoneForWaMe(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '20')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '20' . substr($digits, 1);
        }

        return '20' . $digits;
    }
}
