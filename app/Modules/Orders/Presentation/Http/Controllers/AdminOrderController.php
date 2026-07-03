<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\DTOs\AdminOrderExportFilterDTO;
use App\Modules\Orders\Application\DTOs\AdminOrderFilterDTO;
use App\Modules\Orders\Application\UseCases\Admin\AssignOrderUseCase;
use App\Modules\Orders\Application\UseCases\Admin\BulkDeleteAdminOrdersUseCase;
use App\Modules\Orders\Application\UseCases\Admin\ExportOrdersUseCase;
use App\Modules\Orders\Application\UseCases\Admin\GetAdminOrderDetailUseCase;
use App\Modules\Orders\Application\UseCases\Admin\GetAdminOrderStatsUseCase;
use App\Modules\Orders\Application\UseCases\Admin\ListAdminOrdersUseCase;
use App\Modules\Orders\Application\UseCases\Admin\UpdateAdminOrderStatusUseCase;
use App\Modules\Orders\Application\DTOs\UpdateAdminOrderStatusDTO;
use App\Modules\Orders\Presentation\Http\Requests\Admin\AssignOrderRequest;
use App\Modules\Orders\Presentation\Http\Requests\Admin\BulkDeleteAdminOrdersRequest;
use App\Modules\Orders\Presentation\Http\Requests\Admin\ExportOrdersRequest;
use App\Modules\Orders\Presentation\Http\Requests\Admin\ListAdminOrdersRequest;
use App\Modules\Orders\Presentation\Http\Requests\Admin\UpdateAdminOrderStatusRequest;
use App\Modules\Orders\Presentation\Http\Resources\Admin\AdminOrderDetailResource;
use App\Modules\Orders\Presentation\Http\Resources\Admin\AdminOrderListResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentOrderStatusUpdatedResource;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminOrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetAdminOrderStatsUseCase $getStats,
        private ListAdminOrdersUseCase $listOrders,
        private GetAdminOrderDetailUseCase $getDetail,
        private AssignOrderUseCase $assignOrder,
        private UpdateAdminOrderStatusUseCase $updateStatus,
        private BulkDeleteAdminOrdersUseCase $bulkDeleteOrders,
        private ExportOrdersUseCase $exportOrders,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->getStats->execute();

        return $this->success($stats, __('orders::messages.stats_success'));
    }

    public function index(ListAdminOrdersRequest $request): JsonResponse
    {
        $filter    = AdminOrderFilterDTO::fromArray($request->validated());
        $paginator = $this->listOrders->execute($filter);

        return $this->success(
            array_merge(
                ['items' => AdminOrderListResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('orders::messages.orders_list_success'),
        );
    }

    public function export(ExportOrdersRequest $request): BinaryFileResponse
    {
        $filter = AdminOrderExportFilterDTO::fromArray($request->validated());
        $result = $this->exportOrders->execute($filter);

        return Excel::download($result->export, $result->filename);
    }

    public function show(string $orderId): JsonResponse
    {
        $order = $this->getDetail->execute($orderId);

        return $this->success(
            new AdminOrderDetailResource($order),
            __('orders::messages.order_detail_success'),
        );
    }

    public function assign(AssignOrderRequest $request, string $orderId): JsonResponse
    {
        $order = $this->assignOrder->execute(
            orderId:     $orderId,
            agentId:     $request->validated('agent_id'),
            adminUserId: $request->user()->user_id,
        );

        return $this->success(
            new AdminOrderDetailResource($order),
            __('orders::messages.order_assigned'),
        );
    }

    public function updateStatus(UpdateAdminOrderStatusRequest $request, string $orderId): JsonResponse
    {
        $result = $this->updateStatus->execute(
            UpdateAdminOrderStatusDTO::fromArray(
                orderId: $orderId,
                adminUserId: $request->user()->user_id,
                data: $request->validated(),
            ),
        );

        return $this->success(
            new AgentOrderStatusUpdatedResource($result),
            __('orders::messages.status_updated'),
        );
    }

    public function bulkDestroy(BulkDeleteAdminOrdersRequest $request): JsonResponse
    {
        $this->bulkDeleteOrders->execute($request->validated('ids'));

        return $this->success(null, __('orders::messages.orders_deleted'));
    }
}
