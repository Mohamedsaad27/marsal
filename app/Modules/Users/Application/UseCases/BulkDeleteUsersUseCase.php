<?php

namespace App\Modules\Users\Application\UseCases;

class BulkDeleteUsersUseCase
{
    public function __construct(
        private readonly DeleteUserUseCase $deleteUserUseCase,
    ) {}

    /** @param list<string> $userIds */
    public function execute(array $userIds): void
    {
        foreach (array_values(array_unique($userIds)) as $userId) {
            $this->deleteUserUseCase->execute($userId);
        }
    }
}
