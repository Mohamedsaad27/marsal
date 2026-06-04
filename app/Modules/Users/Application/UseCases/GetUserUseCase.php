<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\Exceptions\UserNotFoundException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;

class GetUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(string $userId): User
    {
        $user = $this->repository->findById($userId);

        if (! $user) {
            throw new UserNotFoundException;
        }

        return $user->load(['roles', 'deliveryAgent', 'shippingCompany', 'staffMember']);
    }
}
