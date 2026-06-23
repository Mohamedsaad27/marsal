<?php

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Users\Application\UseCases\Agent\GetAgentProfileUseCase;
use App\Modules\Users\Application\UseCases\Agent\UpdateAgentFcmTokenUseCase;
use App\Modules\Users\Presentation\Http\Requests\UpdateAgentFcmTokenRequest;
use App\Modules\Users\Presentation\Http\Resources\AgentProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetAgentProfileUseCase $getProfile,
        private UpdateAgentFcmTokenUseCase $updateFcmToken,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $data = $this->getProfile->execute($request->user()->user_id);

        return $this->success(
            new AgentProfileResource($data),
            __('users::messages.agent_profile_success'),
        );
    }

    public function updateFcmToken(UpdateAgentFcmTokenRequest $request): JsonResponse
    {
        $this->updateFcmToken->execute(
            userId: $request->user()->user_id,
            fcmToken: $request->validated('fcm_token'),
        );

        return $this->success(
            null,
            __('users::messages.fcm_token_updated'),
        );
    }
}
