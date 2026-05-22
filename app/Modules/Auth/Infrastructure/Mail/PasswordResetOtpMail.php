<?php

namespace App\Modules\Auth\Infrastructure\Mail;

use App\Modules\Auth\Infrastructure\Support\MailBranding;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetOtpMail extends Mailable
{

    public function __construct(
        public readonly string $userName,
        public readonly string $otp,
        public readonly int $expiryMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: MailBranding::emailSubject('auth::messages.reset_email_subject_suffix'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth::mail.password-reset-otp',
            with: [
                'userName' => $this->userName,
                'otp' => $this->otp,
                'expiryMinutes' => $this->expiryMinutes,
                'logoUrl' => MailBranding::logoUrl(),
                'appName' => MailBranding::appName(),
            ],
        );
    }
}
