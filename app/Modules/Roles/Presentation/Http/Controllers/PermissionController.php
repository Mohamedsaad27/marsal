<?php

namespace App\Modules\Roles\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Roles\Application\UseCases\CreatePermissionUseCase;
use App\Modules\Roles\Application\UseCases\DeletePermissionUseCase;
use App\Modules\Roles\Application\UseCases\ListPermissionsUseCase;
use App\Modules\Roles\Application\UseCases\UpdatePermissionUseCase;
use App\Modules\Roles\Presentation\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PermissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ListPermissionsUseCase $listPermissionsUseCase,
        private readonly CreatePermissionUseCase $createPermissionUseCase,
        private readonly UpdatePermissionUseCase $updatePermissionUseCase,
        private readonly DeletePermissionUseCase $deletePermissionUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $permissions = $this->listPermissionsUseCase->execute();

        return $this->success(PermissionResource::collection($permissions));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100', 'unique:permissions,name']]);

        $permission = $this->createPermissionUseCase->execute($request->string('name')->toString());

        return $this->success(new PermissionResource($permission), __('roles::messages.permission_created'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $permission = $this->updatePermissionUseCase->execute($id, $request->string('name')->toString());

        return $this->success(new PermissionResource($permission), __('roles::messages.permission_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deletePermissionUseCase->execute($id);

        return $this->success(null, __('roles::messages.permission_deleted'));
    }
}
