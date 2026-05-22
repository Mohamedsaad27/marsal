<?php

return [
    'login_success' => 'Logged in successfully',
    'logout_success' => 'Logged out successfully',
    'token_refreshed' => 'Token refreshed successfully',
    'profile_loaded' => 'Profile loaded successfully',
    'invalid_credentials' => 'Invalid login credentials',

    'reset_otp_sent' => 'If this email is registered, a verification code has been sent.',
    'password_reset_success' => 'Password has been reset successfully. You can log in with your new password.',
    'password_changed' => 'Password changed successfully.',
    'invalid_otp' => 'The verification code is incorrect.',
    'otp_expired' => 'The verification code has expired. Please request a new one.',
    'otp_rate_limited' => 'Too many requests. Please try again later.',
    'otp_resend_cooldown' => 'Please wait :seconds seconds before requesting a new code.',
    'invalid_current_password' => 'Current password is incorrect.',
    'password_reuse' => 'The new password must be different from your current password.',
    'password_same_as_email' => 'Password cannot be the same as your email address.',

    'welcome_email_subject_suffix' => 'Welcome — Your account is ready',
    'reset_email_subject_suffix' => 'Password reset code',
    'welcome_email_greeting' => 'Hello :name,',
    'welcome_email_intro' => 'An administrator has created your account on the Mersal platform. Use the credentials below to sign in.',
    'welcome_email_security_note' => 'For your security, change your password after your first login.',
    'welcome_email_cta' => 'Sign in to Mersal',
    'reset_email_greeting' => 'Hello :name,',
    'reset_email_intro' => 'Use the verification code below to reset your password.',
    'reset_email_expiry' => 'This code expires in :minutes minutes.',
    'reset_email_ignore' => 'If you did not request this, you can safely ignore this email.',
    'email_footer' => 'Secure logistics management',
    'email_label_login' => 'Login URL',
    'email_label_email' => 'Email',
    'email_label_phone' => 'Phone',
    'email_label_password' => 'Temporary password',

    'whatsapp_welcome_body' => "Hello :name,\n\nYour :app account is ready.\n\nEmail: :email\nPhone: :phone\nPassword: :password\nLogin: :login_url\n\nPlease change your password after first login.\n\nSupport: :support_phone",
    'whatsapp_admin_hint' => 'Open this link from the dashboard to send the pre-filled WhatsApp welcome message (no API).',
];
