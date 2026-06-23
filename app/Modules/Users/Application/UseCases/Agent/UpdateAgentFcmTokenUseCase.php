<?php

namespace App\Modules\Users\Application\UseCases\Agent;

use App\Modules\Users\Application\Exceptions\UserNotFoundException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;

class UpdateAgentFcmTokenUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function execute(string $userId, string $fcmToken): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $this->users->update($user, ['fcm_token' => $fcmToken]);
    }
}
