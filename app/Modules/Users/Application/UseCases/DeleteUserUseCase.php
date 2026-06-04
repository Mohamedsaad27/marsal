<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\Exceptions\UserActionForbiddenException;
use App\Modules\Users\Application\Exceptions\UserDeletionBlockedException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly GetUserUseCase $getUserUseCase,
    ) {}

    public function execute(string $userId): void
    {
        $user = $this->getUserUseCase->execute($userId);

        if (Auth::id() === $user->user_id) {
            throw new UserActionForbiddenException(__('users::messages.cannot_delete_self'));
        }

        $this->assertCanDelete($user);

        DB::transaction(function () use ($user) {
            $user->deliveryAgent?->delete();
            $user->shippingCompany?->delete();
            $user->staffMember?->delete();

            $this->repository->softDelete($user);
        });
    }

    private function assertCanDelete(User $user): void
    {
        if ($user->hasRole('super_admin')
            && $this->repository->countActiveUsersWithRole('super_admin') <= 1) {
            throw new UserActionForbiddenException(__('users::messages.cannot_delete_last_super_admin'));
        }

        if ($user->deliveryAgent) {
            $agentId = $user->deliveryAgent->delivery_agent_id;

            if ($this->repository->deliveryAgentHasSubordinates($agentId)) {
                throw new UserDeletionBlockedException(__('users::messages.agent_has_subordinates'));
            }

            if ($this->repository->deliveryAgentHasNonTerminalOrders($agentId)) {
                throw new UserDeletionBlockedException(__('users::messages.agent_has_active_orders'));
            }

            if ($this->repository->deliveryAgentHasOpenSettlements($agentId)) {
                throw new UserDeletionBlockedException(__('users::messages.agent_has_open_settlements'));
            }

            if ($this->repository->deliveryAgentHasUnsettledCollections($agentId)) {
                throw new UserDeletionBlockedException(__('users::messages.agent_has_unsettled_collections'));
            }

            if ($this->repository->deliveryAgentHasNonZeroBalance($agentId)) {
                throw new UserDeletionBlockedException(__('users::messages.agent_has_balance'));
            }
        }

        if ($user->shippingCompany) {
            $companyId = $user->shippingCompany->shipping_company_id;

            if ($this->repository->shippingCompanyHasNonTerminalOrders($companyId)) {
                throw new UserDeletionBlockedException(__('users::messages.company_has_active_orders'));
            }

            if ($this->repository->shippingCompanyHasOpenSettlements($companyId)) {
                throw new UserDeletionBlockedException(__('users::messages.company_has_open_settlements'));
            }

            if ($this->repository->shippingCompanyHasUnsettledCollections($companyId)) {
                throw new UserDeletionBlockedException(__('users::messages.company_has_unsettled_collections'));
            }

            if ($this->repository->shippingCompanyHasNonZeroBalance($companyId)) {
                throw new UserDeletionBlockedException(__('users::messages.company_has_balance'));
            }
        }
    }
}
