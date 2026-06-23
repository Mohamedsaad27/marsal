<?php

namespace App\Modules\Users\Domain\Interfaces;

use App\Modules\Users\Application\DTOs\ListDeliveryAgentSupervisorsDTO;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Support\Collection;

interface DeliveryAgentRepositoryInterface
{
    /** @return Collection<int, DeliveryAgent> */
    public function listSupervisors(ListDeliveryAgentSupervisorsDTO $dto): Collection;

    public function findByUserIdForProfile(string $userId): ?DeliveryAgent;

    public function countDeliveredOrders(string $deliveryAgentId): int;
}
