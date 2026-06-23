<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Support\Facades\DB;

class AssignOrderUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
        private SendNotificationUseCase $sendNotification,
    ) {}

    public function execute(string $orderId, string $agentId, string $adminUserId): Order
    {
        $order = $this->repository->findWithRelations($orderId);

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        $agent = DeliveryAgent::query()
            ->with('user')
            ->where('delivery_agent_id', $agentId)
            ->firstOrFail();

        $order = DB::transaction(
            fn () => $this->repository->assignAgent($orderId, $agentId, $adminUserId)
        );

        $this->dispatchNotification($order, $agent);

        return $order;
    }

    private function dispatchNotification(Order $order, DeliveryAgent $agent): void
    {
        if ($agent->user === null) {
            return;
        }

        $this->sendNotification->execute(new SendNotificationDTO(
            userId:           $agent->user->user_id,
            notificationType: NotificationTypeEnum::NewOrder,
            titleAr:          'طلب توصيل جديد',
            bodyAr:           "تم تعيين طلب #{$order->reference_code} لك",
            data:             ['order_id' => $order->order_id],
            sendViaFcm:       true,
        ));
    }
}
