<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Company\GetCompanyDashboardUseCase;
use App\Modules\Orders\Presentation\Http\Resources\Company\CompanyDashboardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetCompanyDashboardUseCase $getDashboard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('orders::messages.company_profile_not_found'), null, 403);
        }

        $data = $this->getDashboard->execute($companyId);

        return $this->success(
            new CompanyDashboardResource($data),
            __('orders::messages.company_dashboard_success'),
        );
    }
}
