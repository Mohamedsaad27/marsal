<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\DTOs\RescheduleOrderDTO;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Application\Exceptions\OrderNotPostponedException;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RescheduleOrderUseCase
{
    public function __construct(
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(RescheduleOrderDTO $dto): array
    {
        $order = $this->orders->findForAgent($dto->orderId, $dto->deliveryAgentId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        if ($order->status !== OrderStatusEnum::Postponed) {
            throw new OrderNotPostponedException();
        }

        $status = OrderStatusEnum::Postponed;

        DB::transaction(function () use ($dto, $order, $status) {
            $now = now();

            $this->updateOrderSchedule($order->order_id, $dto, $now);
            $this->updatePostponedSchedule($order->order_id, $dto, $now);
            $this->recordStatusHistory($order->order_id, $dto, $status, $now);
        });

        return [
            'order_id' => $order->order_id,
            'postponed_date' => $dto->postponedDate,
            'status' => [
                'id' => $status->value,
                'label' => $status->labelAr(),
            ],
        ];
    }

    private function updateOrderSchedule(
        string $orderId,
        RescheduleOrderDTO $dto,
        DateTimeInterface $now,
    ): void {
        DB::table('order_schedules')->upsert(
            [
                [
                    'order_schedule_id' => (string) Str::uuid(),
                    'order_id' => $orderId,
                    'expected_delivery_date' => null,
                    'postponed_date' => $dto->postponedDate,
                    'schedule_notes' => $dto->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['order_id'],
            [
                'postponed_date',
                'schedule_notes',
                'updated_at',
            ],
        );
    }

    private function updatePostponedSchedule(
        string $orderId,
        RescheduleOrderDTO $dto,
        DateTimeInterface $now,
    ): void {
        $existingId = DB::table('postponed_schedules')
            ->where('order_id', $orderId)
            ->where('delivery_agent_id', $dto->deliveryAgentId)
            ->where('is_reassigned', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->value('postponed_schedule_id');

        if ($existingId !== null) {
            DB::table('postponed_schedules')
                ->where('postponed_schedule_id', $existingId)
                ->update([
                    'scheduled_date' => $dto->postponedDate,
                    'reason' => $dto->notes,
                    'reminder_sent' => false,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('postponed_schedules')->insert([
            'postponed_schedule_id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'delivery_agent_id' => $dto->deliveryAgentId,
            'scheduled_date' => $dto->postponedDate,
            'reason' => $dto->notes,
            'reminder_sent' => false,
            'is_reassigned' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function recordStatusHistory(
        string $orderId,
        RescheduleOrderDTO $dto,
        OrderStatusEnum $status,
        DateTimeInterface $now,
    ): void {
        DB::table('order_status_history')->insert([
            'order_status_history_id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'from_status_id' => $status->value,
            'to_status_id' => $status->value,
            'changed_by' => $dto->userId,
            'notes' => $dto->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
