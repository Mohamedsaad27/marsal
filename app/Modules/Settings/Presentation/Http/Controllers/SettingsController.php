<?php

namespace App\Modules\Settings\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Settings\Application\UseCases\GetSettingsUseCase;
use App\Modules\Settings\Application\UseCases\UpdateSettingsUseCase;
use App\Modules\Settings\Presentation\Http\Requests\UpdateSettingsRequest;
use App\Modules\Settings\Presentation\Http\Resources\SettingsResource;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetSettingsUseCase    $getSettings,
        private readonly UpdateSettingsUseCase $updateSettings,
    ) {}

    public function show(): JsonResponse
    {
        $settings = $this->getSettings->execute();

        return $this->success(
            new SettingsResource($settings),
            'Settings fetched successfully'
        );
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->updateSettings->execute($request->toDTO());

        $settings = $this->getSettings->execute();

        return $this->success(
            new SettingsResource($settings),
            'Settings updated successfully'
        );
    }
}
