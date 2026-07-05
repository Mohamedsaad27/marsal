<?php

namespace App\Modules\Reports\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Reports\Application\DTOs\ReportFilterDTO;
use App\Modules\Reports\Application\UseCases\GetCollectionsReportUseCase;
use App\Modules\Reports\Application\UseCases\GetDeliveryAgentsReportUseCase;
use App\Modules\Reports\Application\UseCases\GetOrdersReportUseCase;
use App\Modules\Reports\Application\UseCases\GetSettlementsReportUseCase;
use App\Modules\Reports\Application\UseCases\GetShippingCompaniesReportUseCase;
use App\Modules\Reports\Presentation\Http\Requests\ReportRequest;
use App\Modules\Reports\Presentation\Http\Resources\CollectionsReportResource;
use App\Modules\Reports\Presentation\Http\Resources\DeliveryAgentsReportResource;
use App\Modules\Reports\Presentation\Http\Resources\OrdersReportResource;
use App\Modules\Reports\Presentation\Http\Resources\SettlementsReportResource;
use App\Modules\Reports\Presentation\Http\Resources\ShippingCompaniesReportResource;
use Illuminate\Http\JsonResponse;

class AdminReportsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetOrdersReportUseCase $ordersReport,
        private GetCollectionsReportUseCase $collectionsReport,
        private GetSettlementsReportUseCase $settlementsReport,
        private GetDeliveryAgentsReportUseCase $deliveryAgentsReport,
        private GetShippingCompaniesReportUseCase $shippingCompaniesReport,
    ) {}

    public function orders(ReportRequest $request): JsonResponse
    {
        $report = $this->ordersReport->execute(ReportFilterDTO::fromArray($request->validated()));

        return $this->reportResponse($report, OrdersReportResource::class, __('reports::messages.orders_report_success'));
    }

    public function collections(ReportRequest $request): JsonResponse
    {
        $report = $this->collectionsReport->execute(ReportFilterDTO::fromArray($request->validated()));

        return $this->reportResponse($report, CollectionsReportResource::class, __('reports::messages.collections_report_success'));
    }

    public function settlements(ReportRequest $request): JsonResponse
    {
        $report = $this->settlementsReport->execute(ReportFilterDTO::fromArray($request->validated()));

        return $this->reportResponse($report, SettlementsReportResource::class, __('reports::messages.settlements_report_success'));
    }

    public function deliveryAgents(ReportRequest $request): JsonResponse
    {
        $report = $this->deliveryAgentsReport->execute(ReportFilterDTO::fromArray($request->validated()));

        return $this->reportResponse($report, DeliveryAgentsReportResource::class, __('reports::messages.delivery_agents_report_success'));
    }

    public function shippingCompanies(ReportRequest $request): JsonResponse
    {
        $report = $this->shippingCompaniesReport->execute(ReportFilterDTO::fromArray($request->validated()));

        return $this->reportResponse($report, ShippingCompaniesReportResource::class, __('reports::messages.shipping_companies_report_success'));
    }

    private function reportResponse(array $report, string $resourceClass, string $message): JsonResponse
    {
        $paginator = $report['paginator'];

        return $this->success(
            array_merge(
                [
                    'summary' => $report['summary'],
                    'items' => $resourceClass::collection($paginator->items()),
                ],
                PaginationMeta::getMeta($paginator),
            ),
            $message,
        );
    }
}
