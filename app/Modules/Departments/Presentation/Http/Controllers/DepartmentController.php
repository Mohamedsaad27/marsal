<?php

namespace App\Modules\Departments\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Departments\Application\DTOs\CreateDepartmentDTO;
use App\Modules\Departments\Application\DTOs\UpdateDepartmentDTO;
use App\Modules\Departments\Application\UseCases\CreateDepartmentUseCase;
use App\Modules\Departments\Application\UseCases\DeleteDepartmentUseCase;
use App\Modules\Departments\Application\UseCases\GetDepartmentsKpisUseCase;
use App\Modules\Departments\Application\UseCases\GetDepartmentsUseCase;
use App\Modules\Departments\Application\UseCases\RestoreDepartmentUseCase;
use App\Modules\Departments\Application\UseCases\UpdateDepartmentUseCase;
use App\Modules\Departments\Presentation\Http\Controllers\Concerns\PaginatesResources;
use App\Modules\Departments\Presentation\Http\Requests\StoreDepartmentRequest;
use App\Modules\Departments\Presentation\Http\Requests\UpdateDepartmentRequest;
use App\Modules\Departments\Presentation\Http\Resources\DepartmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DepartmentController extends Controller
{
    use ApiResponseTrait;
    use PaginatesResources;

    public function __construct(
        private readonly GetDepartmentsUseCase $getDepartmentsUseCase,
        private readonly GetDepartmentsKpisUseCase $getDepartmentsKpisUseCase,
        private readonly CreateDepartmentUseCase $createDepartmentUseCase,
        private readonly UpdateDepartmentUseCase $updateDepartmentUseCase,
        private readonly DeleteDepartmentUseCase $deleteDepartmentUseCase,
        private readonly RestoreDepartmentUseCase $restoreDepartmentUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'search' => $request->string('search')->toString() ?: null,
            'is_active' => $request->has('is_active') && $request->input('is_active') !== ''
                ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
        ], fn ($value) => $value !== null);

        $paginator = $this->getDepartmentsUseCase->paginate(
            $filters,
            (int) $request->integer('per_page', 15),
        );

        return $this->paginatedSuccess(
            $paginator,
            DepartmentResource::class,
            __('departments::messages.list_success'),
            ['kpis' => $this->getDepartmentsKpisUseCase->execute()],
        );
    }

    public function show(string $departmentId): JsonResponse
    {
        $department = $this->getDepartmentsUseCase->show($departmentId);

        return $this->success(
            new DepartmentResource($department),
            __('departments::messages.show_success'),
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->createDepartmentUseCase->execute(
            CreateDepartmentDTO::fromArray($request->validated()),
        );

        return $this->success(
            new DepartmentResource($department),
            __('departments::messages.created'),
            201,
        );
    }

    public function update(UpdateDepartmentRequest $request, string $departmentId): JsonResponse
    {
        $department = $this->updateDepartmentUseCase->execute(
            $departmentId,
            UpdateDepartmentDTO::fromArray($request->validated()),
        );

        return $this->success(
            new DepartmentResource($department),
            __('departments::messages.updated'),
        );
    }

    public function destroy(string $departmentId): JsonResponse
    {
        $this->deleteDepartmentUseCase->execute($departmentId);

        return $this->success(null, __('departments::messages.deleted'));
    }

    public function restore(string $departmentId): JsonResponse
    {
        $department = $this->restoreDepartmentUseCase->execute($departmentId);

        return $this->success(
            new DepartmentResource($department),
            __('departments::messages.restored'),
        );
    }
}
