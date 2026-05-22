<?php

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Application\UseCases\CreateUserUseCase;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Presentation\Http\Requests\CreateUserRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreDeliveryAgentRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreStaffMemberRequest;
use App\Modules\Users\Presentation\Http\Requests\StoreShippingCompanyRequest;
use App\Modules\Users\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AdminUserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CreateUserUseCase $createUserUseCase,
    ) {}

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
}
