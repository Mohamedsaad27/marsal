<?php

namespace App\Modules\Orders\Domain\Interfaces;

use App\Modules\Orders\Application\DTOs\AdminOrderExportFilterDTO;
use App\Modules\Orders\Application\DTOs\AdminOrderFilterDTO;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface AdminOrderRepositoryInterface
{
    public function stats(): array;

    public function paginate(AdminOrderFilterDTO $filter): LengthAwarePaginator;

    public function lazyForExport(AdminOrderExportFilterDTO $filter): LazyCollection;

    public function findWithRelations(string $orderId): ?Order;

    public function findById(string $orderId): ?Order;

    public function assignAgent(string $orderId, string $agentId, string $adminUserId): Order;

    public function softDelete(Order $order): void;
}
