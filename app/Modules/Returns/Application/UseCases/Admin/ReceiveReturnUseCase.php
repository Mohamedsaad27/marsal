<?php

namespace App\Modules\Returns\Application\UseCases\Admin;

use App\Modules\Notifications\Domain\Events\ReturnReceivedByAdmin;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReceiveReturnUseCase
{
    public function __construct(
        private ReturnRepositoryInterface $repository,
    ) {}

    public function execute(string $returnId): OrderReturn
    {
        $record = $this->repository->findOrFail($returnId);

        if ($record->return_status !== ReturnStatusEnum::Pending) {
            throw new UnprocessableEntityHttpException(
                __('returns::messages.not_in_pending_status')
            );
        }

        $record = $this->repository->markReceived($returnId);

        $orderCode = Order::query()
            ->where('order_id', $record->order_id)
            ->value('reference_code') ?? $record->order_id;

        event(new ReturnReceivedByAdmin(
            returnId: $record->return_id,
            orderId: $record->order_id,
            orderCode: (string) $orderCode,
            agentName: $record->deliveryAgent?->user?->name ?? 'مندوب',
        ));

        return $record;
    }
}
