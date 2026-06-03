<?php

namespace App\Modules\Auth\Infrastructure\Mail;

use App\Modules\Auth\Infrastructure\Support\MailBranding;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeUserMail extends Mailable
{

    public function __construct(
        public readonly string $userName,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $plainPassword,
        public readonly array $roles = [],
        public readonly bool $isActive = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: MailBranding::emailSubject('auth::messages.welcome_email_subject_suffix'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth::mail.welcome',
            with: [
                'userName' => $this->userName,
                'email' => $this->email,
                'phone' => $this->phone,
                'plainPassword' => $this->plainPassword,
                'roles' => $this->roles,
                'isActive' => $this->isActive,
                'loginUrl' => MailBranding::loginUrl(),
                'logoUrl' => MailBranding::logoUrl(),
                'appName' => MailBranding::appName(),
            ],
        );
    }
}
