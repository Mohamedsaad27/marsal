<?php

namespace App\Modules\Chat\Domain\Services;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;

class ChatAccessService
{
    /**
     * @return array{company_user_id: string, agent_user_id: string}|null
     */
    public function resolveOrderParticipants(Order $order): ?array
    {
        if (! $order->delivery_agent_id || ! $order->shipping_company_id) {
            return null;
        }

        $order->loadMissing([
            'shippingCompany.user',
            'deliveryAgent.user',
        ]);

        $companyUserId = $order->shippingCompany?->user_id;
        $agentUserId = $order->deliveryAgent?->user_id;

        if (! $companyUserId || ! $agentUserId) {
            return null;
        }

        return [
            'company_user_id' => $companyUserId,
            'agent_user_id' => $agentUserId,
        ];
    }

    public function userCanAccessOrderChat(string $userId, Order $order, AccountTypeEnum $accountType): bool
    {
        $participants = $this->resolveOrderParticipants($order);

        if (! $participants) {
            return false;
        }

        return match ($accountType) {
            AccountTypeEnum::ShippingCompany => $participants['company_user_id'] === $userId,
            AccountTypeEnum::DeliveryAgent => $participants['agent_user_id'] === $userId,
            AccountTypeEnum::SuperAdmin, AccountTypeEnum::StaffMember => true,
            default => false,
        };
    }

    public function userCanSendMessage(string $userId, Order $order, AccountTypeEnum $accountType): bool
    {
        return in_array($accountType, [
            AccountTypeEnum::ShippingCompany,
            AccountTypeEnum::DeliveryAgent,
        ], true) && $this->userCanAccessOrderChat($userId, $order, $accountType);
    }

    public function getRecipientUserId(string $senderUserId, array $participants): string
    {
        return $senderUserId === $participants['company_user_id']
            ? $participants['agent_user_id']
            : $participants['company_user_id'];
    }
}
