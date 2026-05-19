<?php

namespace App\Modules\Roles\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Roles\Application\UseCases\CreateRoleUseCase;
use App\Modules\Roles\Application\UseCases\DeleteRoleUseCase;
use App\Modules\Roles\Application\UseCases\ListRolesUseCase;
use App\Modules\Roles\Application\UseCases\SyncRolePermissionsUseCase;
use App\Modules\Roles\Application\UseCases\UpdateRoleUseCase;
use App\Modules\Roles\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\Roles\Presentation\Http\Requests\SyncRolePermissionsRequest;
use App\Modules\Roles\Presentation\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ListRolesUseCase $listRolesUseCase,
        private readonly CreateRoleUseCase $createRoleUseCase,
        private readonly UpdateRoleUseCase $updateRoleUseCase,
        private readonly DeleteRoleUseCase $deleteRoleUseCase,
        private readonly SyncRolePermissionsUseCase $syncRolePermissionsUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $roles = $this->listRolesUseCase->execute();

        return $this->success(RoleResource::collection($roles));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->createRoleUseCase->execute($request->string('name')->toString());

        return $this->success(new RoleResource($role), __('roles::messages.role_created'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $role = $this->updateRoleUseCase->execute($id, $request->string('name')->toString());

        return $this->success(new RoleResource($role), __('roles::messages.role_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteRoleUseCase->execute($id);

        return $this->success(null, __('roles::messages.role_deleted'));
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): JsonResponse
    {
        $role = $this->syncRolePermissionsUseCase->execute($id, $request->input('permissions', []));

        return $this->success(new RoleResource($role), __('roles::messages.permissions_synced'));
    }
}
