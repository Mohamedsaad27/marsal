<?php

namespace App\Modules\Collections\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Application\UseCases\Admin\ListSettlementsUseCase;
use App\Modules\Collections\Application\UseCases\Company\GetCompanySettlementDetailUseCase;
use App\Modules\Collections\Presentation\Http\Requests\Admin\ListSettlementsRequest;
use App\Modules\Collections\Presentation\Http\Resources\Admin\SettlementResource;
use App\Modules\Collections\Presentation\Http\Resources\Company\CompanySettlementDetailResource;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CompanySettlementsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ListSettlementsUseCase $listSettlements,
        private GetCompanySettlementDetailUseCase $getSettlementDetail,
    ) {}

    public function index(ListSettlementsRequest $request): JsonResponse
    {
        $companyId = auth()->user()?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('collections::messages.company_profile_not_found'), null, 403);
        }

        $data = array_merge($request->validated(), ['company_id' => $companyId]);
        $filter = SettlementFilterDTO::fromArray($data);
        $paginator = $this->listSettlements->execute($filter);

        return $this->success(
            array_merge(
                ['items' => SettlementResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('collections::messages.settlements_list_success'),
        );
    }

    public function show(string $settlementId): JsonResponse
    {
        $companyId = auth()->user()?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('collections::messages.company_profile_not_found'), null, 403);
        }

        $settlement = $this->getSettlementDetail->execute($settlementId, $companyId);

        return $this->success(
            new CompanySettlementDetailResource($settlement),
            __('collections::messages.settlement_detail_success'),
        );
    }
}
