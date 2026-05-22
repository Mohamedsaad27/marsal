<?php

return [
    'name' => 'Auth',
    'alias' => 'Auth',

    'login_url' => env('APP_LOGIN_URL', env('APP_URL', 'http://localhost')),
    'logo_path' => env('APP_LOGO_PATH', 'images/logo.png'),
    'mail_app_name' => env('MAIL_APP_NAME', 'شركة مرسال للشحن والتوصيل'),

    'otp_length' => 6,
    'otp_expiry_minutes' => (int) env('AUTH_OTP_EXPIRY_MINUTES', 15),
    'otp_max_attempts' => (int) env('AUTH_OTP_MAX_ATTEMPTS', 5),
    'otp_resend_cooldown_seconds' => (int) env('AUTH_OTP_RESEND_COOLDOWN', 60),
    'otp_max_requests_per_hour' => (int) env('AUTH_OTP_MAX_REQUESTS_PER_HOUR', 5),
    'platform_phone' => (string) env('PLATFORM_PHONE','+201098001021'),
];
