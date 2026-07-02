<?php

namespace App\Modules\Users\Domain\Interfaces;

use App\Modules\Users\Application\DTOs\GetUsersDTO;
use App\Modules\Users\Application\DTOs\ImportUserRowDTO;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    /**
     * @return array{user_id: string}
     */
    public function createUserWithRole(ImportUserRowDTO $dto, string $plainPassword): array;

    public function findById(string $userId): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function findByLogin(string $login): ?User;

    public function updateLastLogin(User $user): void;

    public function updatePassword(User $user, string $plainPassword): void;

    public function updateWelcomeWhatsAppUrl(User $user, string $url): void;

    public function getUsers(GetUsersDTO $dto): LengthAwarePaginator;

    /** @return array{total: int, super_admin: int, staff_member: int, shipping_company: int, delivery_agent: int} */
    public function getUserCounts(): array;

    /** @return array{total: int, active: int, inactive: int} */
    public function getUserCountsForRole(string $role): array;

    public function update(User $user, array $attributes): User;

    public function toggleActive(User $user): User;

    public function softDelete(User $user): void;

    public function countActiveUsersWithRole(string $roleName): int;

    public function deliveryAgentHasSubordinates(string $deliveryAgentId): bool;

    public function deliveryAgentHasNonTerminalOrders(string $deliveryAgentId): bool;

    public function deliveryAgentHasOpenSettlements(string $deliveryAgentId): bool;

    public function deliveryAgentHasUnsettledCollections(string $deliveryAgentId): bool;

    public function deliveryAgentHasUnconfirmedCollections(string $deliveryAgentId): bool;

    public function deliveryAgentHasNonZeroBalance(string $deliveryAgentId): bool;

    public function shippingCompanyHasNonTerminalOrders(string $shippingCompanyId): bool;

    public function shippingCompanyHasOpenSettlements(string $shippingCompanyId): bool;

    public function shippingCompanyHasUnsettledCollections(string $shippingCompanyId): bool;

    public function shippingCompanyHasUnconfirmedCollections(string $shippingCompanyId): bool;

    public function shippingCompanyHasNonZeroBalance(string $shippingCompanyId): bool;
}
