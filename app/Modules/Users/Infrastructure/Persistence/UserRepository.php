<?php

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Application\DTOs\GetUsersDTO;
use App\Modules\Users\Application\DTOs\ImportUserRowDTO;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    /** Terminal order status IDs — OrderStatusEnum terminal cases. */
    private const TERMINAL_ORDER_STATUSES = [5, 6, 7, 8, 9, 10];

    /** Settlement statuses: draft or approved (not yet paid). */
    private const OPEN_SETTLEMENT_STATUSES = [1, 2];

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function createUserWithRole(ImportUserRowDTO $dto, string $plainPassword): array
    {
        return DB::transaction(function () use ($dto, $plainPassword) {
            $user = User::query()->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'gender' => $dto->gender,
                'password' => $plainPassword,
                'is_active' => true,
            ]);

            $user->assignRole($dto->role);

            if ($dto->role === 'delivery_agent') {
                DeliveryAgent::query()->create([
                    'user_id' => $user->user_id,
                ]);
            }

            if ($dto->role === 'shipping_company') {
                ShippingCompany::query()->create([
                    'user_id' => $user->user_id,
                    'company_name' => $dto->companyName ?? $dto->name,
                    'commission_type' => 1,
                    'commission_value' => 0,
                ]);
            }

            return ['user_id' => $user->user_id];
        });
    }

    public function findById(string $userId): ?User
    {
        return User::query()->with('addresses')->find($userId);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->with('addresses')->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return User::query()->with('addresses')->where('phone', $phone)->first();
    }

    public function findByLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($login);
        }

        return $this->findByPhone($login);
    }

    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->update(['password' => $plainPassword]);
    }

    public function updateWelcomeWhatsAppUrl(User $user, string $url): void
    {
        $user->update(['welcome_whatsapp_url' => $url]);
    }

    public function getUsers(GetUsersDTO $dto): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'deliveryAgent', 'shippingCompany', 'staffMember', 'addresses']);

        if ($dto->search) {
            $term = '%'.$dto->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                    ->orWhere('email', 'LIKE', $term)
                    ->orWhere('phone', 'LIKE', $term);
            });
        }

        if ($dto->role !== null) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $dto->role));
        }

        if ($dto->isActive !== null) {
            $query->where('is_active', $dto->isActive);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    public function getUserCounts(): array
    {
        $modelType = User::class;

        $counts = DB::table('users')
            ->join('model_has_roles', function ($join) use ($modelType) {
                $join->on('users.user_id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', $modelType);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->selectRaw('
                COUNT(DISTINCT users.user_id) AS total,
                SUM(CASE WHEN roles.name = ? THEN 1 ELSE 0 END) AS super_admin,
                SUM(CASE WHEN roles.name = ? THEN 1 ELSE 0 END) AS staff_member,
                SUM(CASE WHEN roles.name = ? THEN 1 ELSE 0 END) AS shipping_company,
                SUM(CASE WHEN roles.name = ? THEN 1 ELSE 0 END) AS delivery_agent
            ', ['super_admin', 'staff_member', 'shipping_company', 'delivery_agent'])
            ->first();

        return [
            'total' => (int) ($counts->total ?? 0),
            'super_admin' => (int) ($counts->super_admin ?? 0),
            'staff_member' => (int) ($counts->staff_member ?? 0),
            'shipping_company' => (int) ($counts->shipping_company ?? 0),
            'delivery_agent' => (int) ($counts->delivery_agent ?? 0),
        ];
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        return $user->fresh();
    }

    public function softDelete(User $user): void
    {
        $user->delete();
    }

    public function countActiveUsersWithRole(string $roleName): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->count();
    }

    public function deliveryAgentHasSubordinates(string $deliveryAgentId): bool
    {
        return DB::table('delivery_agents')
            ->where('supervisor_agent_id', $deliveryAgentId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function deliveryAgentHasNonTerminalOrders(string $deliveryAgentId): bool
    {
        return DB::table('orders')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', self::TERMINAL_ORDER_STATUSES)
            ->exists();
    }

    public function deliveryAgentHasOpenSettlements(string $deliveryAgentId): bool
    {
        return DB::table('settlements')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('deleted_at')
            ->whereIn('settlement_status', self::OPEN_SETTLEMENT_STATUSES)
            ->exists();
    }

    public function deliveryAgentHasUnsettledCollections(string $deliveryAgentId): bool
    {
        return DB::table('collections')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('settlement_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    public function deliveryAgentHasNonZeroBalance(string $deliveryAgentId): bool
    {
        return DB::table('delivery_agents')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->where('balance', '>', 0)
            ->exists();
    }

    public function shippingCompanyHasNonTerminalOrders(string $shippingCompanyId): bool
    {
        return DB::table('orders')
            ->where('shipping_company_id', $shippingCompanyId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', self::TERMINAL_ORDER_STATUSES)
            ->exists();
    }

    public function shippingCompanyHasOpenSettlements(string $shippingCompanyId): bool
    {
        return DB::table('settlements')
            ->where('shipping_company_id', $shippingCompanyId)
            ->whereNull('deleted_at')
            ->whereIn('settlement_status', self::OPEN_SETTLEMENT_STATUSES)
            ->exists();
    }

    public function shippingCompanyHasUnsettledCollections(string $shippingCompanyId): bool
    {
        return DB::table('collections')
            ->where('shipping_company_id', $shippingCompanyId)
            ->whereNull('settlement_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    public function shippingCompanyHasNonZeroBalance(string $shippingCompanyId): bool
    {
        return DB::table('shipping_companies')
            ->where('shipping_company_id', $shippingCompanyId)
            ->where('balance', '>', 0)
            ->exists();
    }
}
