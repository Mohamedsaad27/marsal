<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\GetUsersDTO;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;

class GetUsersUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(GetUsersDTO $dto): array
    {
        return [
            'users' => $this->repository->getUsers($dto),
            'counts' => $this->repository->getUserCounts(),
        ];
    }
}
