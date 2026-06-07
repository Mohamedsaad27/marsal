<?php

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Users\Application\Actions\UpdateProfileAction;
use App\Modules\Users\Application\DTOs\UpdateProfileData;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Presentation\Http\Requests\UpdateProfileRequest;
use App\Modules\Users\Presentation\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UpdateProfileAction $updateProfileAction,
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user = $this->updateProfileAction->execute(
            $user,
            UpdateProfileData::fromRequest($request),
        );

        return $this->success(new ProfileResource($user), __('users::messages.profile_updated'));
    }
}
