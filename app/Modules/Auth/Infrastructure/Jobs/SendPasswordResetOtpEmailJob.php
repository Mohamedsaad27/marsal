<?php

namespace App\Modules\Auth\Infrastructure\Jobs;

use App\Modules\Auth\Infrastructure\Mail\PasswordResetOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetOtpEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $email,
        public readonly string $userName,
        public readonly string $otp,
    ) {}

    public function handle(): void
    {
        $expiryMinutes = (int) config('auth_module.otp_expiry_minutes', 15);

        Mail::to($this->email)->send(new PasswordResetOtpMail(
            userName: $this->userName,
            otp: $this->otp,
            expiryMinutes: $expiryMinutes,
        ));
    }
}
