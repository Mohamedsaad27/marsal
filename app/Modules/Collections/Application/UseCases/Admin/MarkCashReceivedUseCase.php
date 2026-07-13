<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Notifications\Domain\Events\CollectionCashReceived;

class MarkCashReceivedUseCase
{
    public function __construct(
        private AdminCollectionRepositoryInterface $repository,
    ) {}

    public function execute(string $collectionId, string $receivedBy): Collection
    {
        $collection = $this->repository->markCashReceived($collectionId, $receivedBy);

        event(new CollectionCashReceived(
            collectionId: $collection->collection_id,
            orderId: $collection->order_id,
            orderCode: $collection->order?->reference_code ?? $collection->order?->reference_no ?? '',
            agentName: $collection->deliveryAgent?->user?->name ?? 'المندوب',
            collectedAmount: number_format((float) $collection->collected_amount, 2, '.', ''),
            agentUserId: $collection->deliveryAgent?->user?->user_id,
            companyUserId: $collection->shippingCompany?->user?->user_id,
        ));

        return $collection;
    }
}
