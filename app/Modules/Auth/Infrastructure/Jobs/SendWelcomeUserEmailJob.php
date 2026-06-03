<?php

namespace App\Modules\Auth\Infrastructure\Jobs;

use App\Modules\Auth\Infrastructure\Mail\WelcomeUserMail;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWelcomeUserEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $userId,
        public readonly string $plainPassword,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || empty($user->email)) {
            return;
        }

        Mail::to($user->email)->send(new WelcomeUserMail(
            userName: $user->name,
            email: $user->email,
            phone: $user->phone,
            plainPassword: $this->plainPassword,
            roles: $user->getRoleNames()->all(),
            isActive: (bool) $user->is_active,
        ));
    }
}
