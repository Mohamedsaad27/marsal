<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\DTOs\RescheduleOrderDTO;
use App\Modules\Orders\Application\DTOs\UpdateAgentOrderStatusDTO;
use App\Modules\Orders\Application\DTOs\UploadOrderProofDTO;
use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Application\UseCases\Agent\GetAgentOrderDetailUseCase;
use App\Modules\Orders\Application\UseCases\Agent\ListAgentOrdersUseCase;
use App\Modules\Orders\Application\UseCases\Agent\RescheduleOrderUseCase;
use App\Modules\Orders\Application\UseCases\Agent\UpdateAgentOrderStatusUseCase;
use App\Modules\Orders\Application\UseCases\Agent\UploadDeliveryProofUseCase;
use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use App\Modules\Orders\Presentation\Http\Requests\RescheduleOrderRequest;
use App\Modules\Orders\Presentation\Http\Requests\UpdateAgentOrderStatusRequest;
use App\Modules\Orders\Presentation\Http\Requests\UploadDeliveryProofRequest;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderDetailResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderListResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderProofResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderRescheduledResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderStatusUpdatedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentOrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ListAgentOrdersUseCase $listOrders,
        private GetAgentOrderDetailUseCase $getOrderDetail,
        private UpdateAgentOrderStatusUseCase $updateStatus,
        private UploadDeliveryProofUseCase $uploadProof,
        private RescheduleOrderUseCase $rescheduleOrder,
        private AgentContextService $agentContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $paginator = $this->listOrders->execute(
            userId: $request->user()->user_id,
            statusFilter: $request->query('status'),
            search: $request->query('search'),
            perPage: $perPage,
        );

        return $this->success(
            array_merge(
                ['items' => AgentOrderListResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('orders::messages.orders_list_success'),
        );
    }

    public function show(Request $request, string $orderId): JsonResponse
    {
        $order = $this->getOrderDetail->execute($request->user()->user_id, $orderId);

        return $this->success(
            new AgentOrderDetailResource($order),
            __('orders::messages.order_detail_success'),
        );
    }

    public function updateStatus(UpdateAgentOrderStatusRequest $request, string $orderId): JsonResponse
    {
        $userId = $request->user()->user_id;
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        $result = $this->updateStatus->execute(
            UpdateAgentOrderStatusDTO::fromArray(
                orderId: $orderId,
                deliveryAgentId: $deliveryAgentId,
                userId: $userId,
                data: $request->validated(),
            ),
        );

        return $this->success(
            new AgentOrderStatusUpdatedResource($result),
            __('orders::messages.status_updated'),
        );
    }

    public function uploadProof(UploadDeliveryProofRequest $request, string $orderId): JsonResponse
    {
        $userId = $request->user()->user_id;
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        $result = $this->uploadProof->execute(new UploadOrderProofDTO(
            orderId: $orderId,
            deliveryAgentId: $deliveryAgentId,
            userId: $userId,
            photo: $request->file('photo'),
            fileType: OrderProofFileTypeEnum::from((int) $request->input('file_type')),
        ));

        return $this->success(
            new AgentOrderProofResource($result),
            __('orders::messages.proof_uploaded'),
        );
    }

    public function reschedule(RescheduleOrderRequest $request, string $orderId): JsonResponse
    {
        $userId = $request->user()->user_id;
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        $result = $this->rescheduleOrder->execute(
            RescheduleOrderDTO::fromArray(
                orderId: $orderId,
                deliveryAgentId: $deliveryAgentId,
                userId: $userId,
                data: $request->validated(),
            ),
        );

        return $this->success(
            new AgentOrderRescheduledResource($result),
            __('orders::messages.order_rescheduled'),
        );
    }
}
