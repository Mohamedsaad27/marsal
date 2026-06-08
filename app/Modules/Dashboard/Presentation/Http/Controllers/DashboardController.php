<?php

namespace App\Modules\Dashboard\Presentation\Http\Controllers;

use App\Modules\Dashboard\Application\Queries\GetAvgDeliveryTimeQuery;
use App\Modules\Dashboard\Application\Queries\GetCollectionsBalanceQuery;
use App\Modules\Dashboard\Application\Queries\GetDashboardSummaryQuery;
use App\Modules\Dashboard\Application\Queries\GetDeliveryPerformanceQuery;
use App\Modules\Dashboard\Application\Queries\GetRecentOrdersQuery;
use App\Modules\Dashboard\Application\Queries\GetShipmentsChartQuery;
use App\Modules\Dashboard\Application\Queries\GetTopAgentsQuery;
use App\Modules\Dashboard\Presentation\Http\Requests\RecentOrdersRequest;
use App\Modules\Dashboard\Presentation\Http\Resources\AvgDeliveryTimeResource;
use App\Modules\Dashboard\Presentation\Http\Resources\CollectionsBalanceResource;
use App\Modules\Dashboard\Presentation\Http\Resources\DashboardSummaryResource;
use App\Modules\Dashboard\Presentation\Http\Resources\DeliveryPerformanceResource;
use App\Modules\Dashboard\Presentation\Http\Resources\RecentOrderResource;
use App\Modules\Dashboard\Presentation\Http\Resources\ShipmentsChartResource;
use App\Modules\Dashboard\Presentation\Http\Resources\TopAgentResource;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetDashboardSummaryQuery $getDashboardSummary,
        private readonly GetShipmentsChartQuery $getShipmentsChart,
        private readonly GetTopAgentsQuery $getTopAgents,
        private readonly GetCollectionsBalanceQuery $getCollectionsBalance,
        private readonly GetDeliveryPerformanceQuery $getDeliveryPerformance,
        private readonly GetAvgDeliveryTimeQuery $getAvgDeliveryTime,
        private readonly GetRecentOrdersQuery $getRecentOrders,
    ) {}

    public function summary(): JsonResponse
    {
        // TODO: add permission check

        $data = $this->getDashboardSummary->execute();

        return $this->success(
            new DashboardSummaryResource($data),
            __('dashboard::dashboard.summary_fetched'),
        );
    }

    public function shipmentsChart(Request $request): JsonResponse
    {
        // TODO: add permission check

        $data = $this->getShipmentsChart->execute($request->query('period', 'week'));

        return $this->success(
            new ShipmentsChartResource($data),
            __('dashboard::dashboard.shipments_chart_fetched'),
        );
    }

    public function topAgents(): JsonResponse
    {
        // TODO: add permission check

        $agents = $this->getTopAgents->execute();

        return $this->success(
            TopAgentResource::collection($agents),
            __('dashboard::dashboard.top_agents_fetched'),
        );
    }

    public function collectionsBalance(): JsonResponse
    {
        // TODO: add permission check

        $data = $this->getCollectionsBalance->execute();

        return $this->success(
            new CollectionsBalanceResource($data),
            __('dashboard::dashboard.collections_balance_fetched'),
        );
    }

    public function deliveryPerformance(): JsonResponse
    {
        // TODO: add permission check

        $data = $this->getDeliveryPerformance->execute();

        return $this->success(
            new DeliveryPerformanceResource($data),
            __('dashboard::dashboard.delivery_performance_fetched'),
        );
    }

    public function avgDeliveryTime(): JsonResponse
    {
        // TODO: add permission check

        $data = $this->getAvgDeliveryTime->execute();

        return $this->success(
            new AvgDeliveryTimeResource($data),
            __('dashboard::dashboard.avg_delivery_time_fetched'),
        );
    }

    public function recentOrders(RecentOrdersRequest $request): JsonResponse
    {
        // TODO: add permission check

        $paginator = $this->getRecentOrders->execute($request->validated());

        return response()->json([
            'isSuccess' => true,
            'message' => __('dashboard::dashboard.recent_orders_fetched'),
            'data' => RecentOrderResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
