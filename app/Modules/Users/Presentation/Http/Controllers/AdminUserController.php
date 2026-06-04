<?php

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Users\Application\DTOs\AdminChangeUserPasswordDTO;
use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Application\DTOs\GetUsersDTO;
use App\Modules\Users\Application\DTOs\UpdateUserDTO;
use App\Modules\Users\Application\Exceptions\ImportUsersUnreadableFileException;
use App\Modules\Users\Application\UseCases\AdminChangeUserPasswordUseCase;
use App\Modules\Users\Application\UseCases\CreateUserUseCase;
use App\Modules\Users\Application\UseCases\DeleteUserUseCase;
use App\Modules\Users\Application\UseCases\GetUsersUseCase;
use App\Modules\Users\Application\UseCases\ImportUsersUseCase;
use App\Modules\Users\Application\UseCases\ToggleUserStatusUseCase;
use App\Modules\Users\Application\UseCases\UpdateUserUseCase;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Excel\UsersImportTemplateExport;
use App\Modules\Users\Presentation\Http\Requests\AdminChangeUserPasswordRequest;
use App\Modules\Users\Presentation\Http\Requests\CreateUserRequest;
use App\Modules\Users\Presentation\Http\Requests\GetUsersRequest;
use App\Modules\Users\Presentation\Http\Requests\ImportUsersRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreDeliveryAgentRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreShippingCompanyRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreStaffMemberRequest;
use App\Modules\Users\Presentation\Http\Requests\UpdateUserRequest;
use App\Modules\Users\Presentation\Http\Resources\ImportUsersResultResource;
use App\Modules\Users\Presentation\Http\Resources\UserListResource;
use App\Modules\Users\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminUserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly AdminChangeUserPasswordUseCase $adminChangeUserPasswordUseCase,
        private readonly GetUsersUseCase $getUsersUseCase,
        private readonly UpdateUserUseCase $updateUserUseCase,
        private readonly ToggleUserStatusUseCase $toggleUserStatusUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly ImportUsersUseCase $importUsersUseCase,
    ) {}

    public function index(GetUsersRequest $request): JsonResponse
    {
        $result = $this->getUsersUseCase->execute(GetUsersDTO::fromArray($request->validated()));
        $paginator = $result['users'];

        return $this->success(array_merge(
            ['counts' => $result['counts']],
            ['items' => UserListResource::collection($paginator->items())],
            PaginationMeta::getMeta($paginator),
        ), __('users::messages.fetched_successfully'));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {

        $user = $this->createUserUseCase->execute($request->toDTO());

        return $this->success(new UserResource($user), __('users::messages.user_created'), 201);
    }

    public function storeShippingCompany(StoreShippingCompanyRequest $request): JsonResponse
    {
        $dto = new CreateUserDTO(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->string('phone')->toString(),
            password: $request->string('password')->toString(),
            accountType: AccountTypeEnum::ShippingCompany,
            roles: $request->input('roles', ['shipping_company']),
            profile: $request->input('profile', []),
        );

        $user = $this->createUserUseCase->execute($dto);

        return $this->success(new UserResource($user), __('users::messages.company_created'), 201);
    }

    public function storeDeliveryAgent(StoreDeliveryAgentRequest $request): JsonResponse
    {
        $dto = new CreateUserDTO(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->string('phone')->toString(),
            password: $request->string('password')->toString(),
            accountType: AccountTypeEnum::DeliveryAgent,
            roles: $request->input('roles', ['delivery_agent']),
            profile: $request->input('profile', []),
        );

        $user = $this->createUserUseCase->execute($dto);

        return $this->success(new UserResource($user), __('users::messages.agent_created'), 201);
    }

    public function storeStaffMember(StoreStaffMemberRequest $request): JsonResponse
    {
        $dto = new CreateUserDTO(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->string('phone')->toString(),
            password: $request->string('password')->toString(),
            accountType: AccountTypeEnum::StaffMember,
            roles: $request->input('roles', ['staff_member']),
            profile: $request->input('profile', []),
        );

        $user = $this->createUserUseCase->execute($dto);

        return $this->success(new UserResource($user), __('users::messages.staff_created'), 201);
    }

    public function update(UpdateUserRequest $request, string $userId): JsonResponse
    {
        $user = $this->updateUserUseCase->execute(
            UpdateUserDTO::fromArray($userId, $request->validated()),
        );

        return $this->success(new UserResource($user), __('users::messages.user_updated'));
    }

    public function toggleStatus(string $userId): JsonResponse
    {
        $user = $this->toggleUserStatusUseCase->execute($userId);

        return $this->success(
            new UserListResource($user->load(['roles', 'deliveryAgent', 'shippingCompany', 'staffMember'])),
            __('users::messages.user_status_toggled'),
        );
    }

    public function destroy(string $userId): JsonResponse
    {
        $this->deleteUserUseCase->execute($userId);

        return $this->success(null, __('users::messages.user_deleted'));
    }

    public function changePassword(AdminChangeUserPasswordRequest $request, string $userId): JsonResponse
    {
        $dto = AdminChangeUserPasswordDTO::fromArray($userId, $request->validated());
        $this->adminChangeUserPasswordUseCase->execute($dto);

        return $this->success(null, __('users::messages.password_changed_successfully'));
    }

    public function import(ImportUsersRequest $request): JsonResponse
    {
        try {
            $result = $this->importUsersUseCase->execute($request->file('file'));
        } catch (ImportUsersUnreadableFileException) {
            return $this->error(__('users::messages.import_file_unreadable'), null, 422);
        }

        $message = $result->failedCount === 0
            ? __('users::messages.import_all_success', ['count' => $result->importedCount])
            : __('users::messages.import_partial_success', [
                'imported' => $result->importedCount,
                'failed' => $result->failedCount,
            ]);

        return $this->success(new ImportUsersResultResource($result), $message);
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new UsersImportTemplateExport,
            'users_import_template.xlsx',
        );
    }
}
