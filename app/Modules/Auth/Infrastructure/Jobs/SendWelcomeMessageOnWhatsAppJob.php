<?php

namespace App\Modules\Auth\Infrastructure\Jobs;

use App\Modules\Auth\Domain\Services\WhatsAppService;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Optional async regeneration of the welcome WhatsApp link (same as sync use case).
 */
class SendWelcomeMessageOnWhatsAppJob implements ShouldQueue
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

    public function handle(WhatsAppService $whatsAppService, UserRepositoryInterface $userRepository): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null || empty($user->phone)) {
            return;
        }

        $url = $whatsAppService->buildWelcomeLink($user, $this->plainPassword);
        $userRepository->updateWelcomeWhatsAppUrl($user, $url);
    }
}
