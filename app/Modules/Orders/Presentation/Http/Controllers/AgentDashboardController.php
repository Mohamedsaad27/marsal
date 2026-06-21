<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Agent\GetAgentDashboardUseCase;
use App\Modules\Orders\Presentation\Http\Resources\AgentDashboardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetAgentDashboardUseCase $getDashboard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->getDashboard->execute($request->user()->user_id);

        return $this->success(
            new AgentDashboardResource($data),
            __('orders::messages.dashboard_success'),
        );
    }
}
