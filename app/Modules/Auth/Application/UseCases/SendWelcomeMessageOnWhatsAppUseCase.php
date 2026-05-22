<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Services\WhatsAppService;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;

class SendWelcomeMessageOnWhatsAppUseCase
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Generates wa.me link and stores it on the user for the admin to open and send manually.
     */
    public function execute(User $user, string $plainPassword): ?string
    {
        if (empty($user->phone)) {
            return null;
        }

        $url = $this->whatsAppService->buildWelcomeLink($user, $plainPassword);
        $this->userRepository->updateWelcomeWhatsAppUrl($user, $url);

        return $url;
    }
}
