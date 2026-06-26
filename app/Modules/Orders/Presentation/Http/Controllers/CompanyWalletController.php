<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Company\GetCompanyWalletUseCase;
use App\Modules\Orders\Presentation\Http\Resources\Company\CompanyWalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyWalletController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetCompanyWalletUseCase $getWallet,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->shippingCompany?->shipping_company_id;

        if ($companyId === null) {
            return $this->error(__('orders::messages.company_profile_not_found'), null, 403);
        }

        $balance = (float) ($user->shippingCompany->balance ?? 0);
        $data = $this->getWallet->execute($companyId, $balance);

        return $this->success(
            new CompanyWalletResource($data),
            __('orders::messages.company_wallet_success'),
        );
    }
}
