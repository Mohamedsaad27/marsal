<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Support\Collection;

class BulkAssignOrdersUseCase
{
    public function __construct(
        private AssignOrderUseCase $assignOrder,
    ) {}

    /** @return Collection<int, Order> */
    public function execute(array $orderIds, string $agentId, string $adminUserId): Collection
    {
        return $this->assignOrder->executeMany($orderIds, $agentId, $adminUserId);
    }
}
