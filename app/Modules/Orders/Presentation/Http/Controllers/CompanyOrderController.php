<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\DTOs\CompanyOrderFilterDTO;
use App\Modules\Orders\Application\UseCases\Company\GetCompanyOrderDetailUseCase;
use App\Modules\Orders\Application\UseCases\Company\ListCompanyOrdersUseCase;
use App\Modules\Orders\Presentation\Http\Requests\Company\ListCompanyOrdersRequest;
use App\Modules\Orders\Presentation\Http\Resources\Company\CompanyOrderDetailResource;
use App\Modules\Orders\Presentation\Http\Resources\Company\CompanyOrderListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyOrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ListCompanyOrdersUseCase $listOrders,
        private GetCompanyOrderDetailUseCase $getOrderDetail,
    ) {}

    public function index(ListCompanyOrdersRequest $request): JsonResponse
    {
        $companyId = $request->user()?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('orders::messages.company_profile_not_found'), null, 403);
        }

        $filter = CompanyOrderFilterDTO::fromArray($request->validated());
        $paginator = $this->listOrders->execute($filter, $companyId);

        return $this->success(
            array_merge(
                ['items' => CompanyOrderListResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('orders::messages.company_orders_list_success'),
        );
    }

    public function show(Request $request, string $orderId): JsonResponse
    {
        $companyId = $request->user()?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('orders::messages.company_profile_not_found'), null, 403);
        }

        $order = $this->getOrderDetail->execute($orderId, $companyId);

        return $this->success(
            new CompanyOrderDetailResource($order),
            __('orders::messages.company_order_detail_success'),
        );
    }
}
