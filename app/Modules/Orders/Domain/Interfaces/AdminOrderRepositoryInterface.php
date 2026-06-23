<?php

namespace App\Modules\Orders\Domain\Interfaces;

use App\Modules\Orders\Application\DTOs\AdminOrderFilterDTO;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminOrderRepositoryInterface
{
    public function stats(): array;

    public function paginate(AdminOrderFilterDTO $filter): LengthAwarePaginator;

    public function findWithRelations(string $orderId): ?Order;

    public function assignAgent(string $orderId, string $agentId, string $adminUserId): Order;
}
