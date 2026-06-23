<?php

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Application\DTOs\ListDeliveryAgentSupervisorsDTO;
use App\Modules\Users\Domain\Interfaces\DeliveryAgentRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryAgentRepository implements DeliveryAgentRepositoryInterface
{
    public function listSupervisors(ListDeliveryAgentSupervisorsDTO $dto): Collection
    {
        $query = DeliveryAgent::query()
            ->select('delivery_agents.*')
            ->join('users', 'users.user_id', '=', 'delivery_agents.user_id')
            ->whereNull('delivery_agents.supervisor_agent_id')
            ->whereNull('delivery_agents.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'delivery_agent'));

        if ($dto->search !== null) {
            $term = '%'.$dto->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('users.name', 'LIKE', $term)
                    ->orWhere('users.phone', 'LIKE', $term);
            });
        }

        if ($dto->isActive !== null) {
            $query->where('users.is_active', $dto->isActive);
        }

        return $query
            ->with(['user' => fn ($q) => $q->select('user_id', 'name', 'phone', 'is_active')])
            ->orderBy('users.name')
            ->get();
    }

    public function findByUserIdForProfile(string $userId): ?DeliveryAgent
    {
        return DeliveryAgent::query()
            ->with([
                'user',
                'zones.city',
                'zones.governorate',
            ])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function countDeliveredOrders(string $deliveryAgentId): int
    {
        return (int) DB::table('orders')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('deleted_at')
            ->whereIn('status', [
                OrderStatusEnum::Delivered->value,
                OrderStatusEnum::DeliveredPriceChanged->value,
                OrderStatusEnum::PartialDelivery->value,
            ])
            ->count();
    }
}
