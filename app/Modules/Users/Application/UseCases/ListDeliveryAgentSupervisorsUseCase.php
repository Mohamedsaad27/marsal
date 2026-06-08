<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\ListDeliveryAgentSupervisorsDTO;
use App\Modules\Users\Domain\Interfaces\DeliveryAgentRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Support\Collection;

class ListDeliveryAgentSupervisorsUseCase
{
    public function __construct(
        private readonly DeliveryAgentRepositoryInterface $repository,
    ) {}

    /** @return Collection<int, DeliveryAgent> */
    public function execute(ListDeliveryAgentSupervisorsDTO $dto): Collection
    {
        return $this->repository->listSupervisors($dto);
    }
}
