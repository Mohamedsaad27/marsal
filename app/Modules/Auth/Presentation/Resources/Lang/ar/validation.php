<?php

return [
    'attributes' => [
        'identifier' => 'البريد الإلكتروني أو رقم الهاتف',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'email' => 'البريد الإلكتروني',
        'otp' => 'رمز التحقق',
    ],

    'messages' => [
        'identifier.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
        'identifier.string' => 'بيانات الدخول يجب أن تكون نصاً.',
        'identifier.max' => 'بيانات الدخول طويلة جداً (الحد الأقصى 255 حرفاً).',

        'password.required' => 'كلمة المرور مطلوبة.',
        'password.string' => 'كلمة المرور يجب أن تكون نصاً.',
        'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
    ],
];
