<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\AdminChangeUserPasswordDTO;
use App\Modules\Users\Application\Exceptions\UserNotFoundException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;

class AdminChangeUserPasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(AdminChangeUserPasswordDTO $dto): void
    {
        $user = $this->repository->findById($dto->userId);

        if (! $user) {
            throw new UserNotFoundException();
        }

        $this->repository->updatePassword($user, $dto->password);
    }
}
