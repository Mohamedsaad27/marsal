<?php

namespace App\Modules\Orders\Domain\Interfaces;

use App\Modules\Orders\Application\DTOs\CompanyOrderFilterDTO;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompanyOrderRepositoryInterface
{
    public function paginate(CompanyOrderFilterDTO $filter, string $companyId): LengthAwarePaginator;

    public function findForCompany(string $orderId, string $companyId): ?Order;

    /**
     * Returns: total_orders, in_delivery_count, collected_today, delivery_rate_percent
     */
    public function getDashboardStats(string $companyId): array;

    /**
     * @return Collection<int, Order>
     */
    public function getRecentOrders(string $companyId, int $limit = 5): Collection;

    /**
     * Returns: total_orders, delivery_rate_percent — used on profile screen
     */
    public function getOrderStats(string $companyId): array;

    /**
     * Returns: total_collected, total_commissions, total_net_due,
     *          pending_settlement_amount, pending_collection_count
     */
    public function getWalletAggregates(string $companyId): array;
}
