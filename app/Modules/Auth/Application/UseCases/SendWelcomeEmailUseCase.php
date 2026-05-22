<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Infrastructure\Jobs\SendWelcomeUserEmailJob;
use App\Modules\Users\Infrastructure\Database\Models\User;

class SendWelcomeEmailUseCase
{
    public function execute(User $user, string $plainPassword): void
    {
        if (empty($user->email)) {
            return;
        }

        SendWelcomeUserEmailJob::dispatch($user->user_id, $plainPassword);

    }
}
