<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Company\GetCompanyProfileUseCase;
use App\Modules\Orders\Presentation\Http\Resources\Company\CompanyProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetCompanyProfileUseCase $getProfile,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $shippingCompany = $user?->shippingCompany;

        if ($shippingCompany === null) {
            return $this->error(__('orders::messages.company_profile_not_found'), null, 403);
        }

        $data = $this->getProfile->execute(
            companyId: $shippingCompany->shipping_company_id,
            companyData: [
                'company_id'     => $shippingCompany->shipping_company_id,
                'company_name'   => $shippingCompany->company_name,
                'commercial_reg' => $shippingCompany->commercial_reg,
                'logo_url'       => $shippingCompany->logo_url,
                'balance'        => (float) ($shippingCompany->balance ?? 0),
                'phone'          => $user->phone,
            ],
        );

        return $this->success(
            new CompanyProfileResource($data),
            __('orders::messages.company_profile_success'),
        );
    }
}
