<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Agent\GetAgentDefinitionsUseCase;
use App\Modules\Orders\Presentation\Http\Resources\AgentDefinitionsResource;
use Illuminate\Http\JsonResponse;

class AgentDefinitionsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetAgentDefinitionsUseCase $getDefinitions,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            new AgentDefinitionsResource($this->getDefinitions->execute()),
            __('orders::messages.definitions_success'),
        );
    }
}
