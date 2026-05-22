<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\StaffMember;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Modules\Auth\Application\Exceptions\RoleNotFoundException;
class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(CreateUserDTO $dto): User
    {
        $this->validateRolesExist($dto->roles);
        
        return DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'password' => $dto->password,
                'is_active' => true,
            ]);

            $user->syncRoles($dto->roles);

            if ($dto->accountType->requiresStaffMemberProfile()) {
                StaffMember::query()->create([
                    'user_id' => $user->user_id,
                    'department' => $dto->profile['department'] ?? null,
                    'job_title' => $dto->profile['job_title'] ?? null,
                    'notes' => $dto->profile['notes'] ?? null,
                ]);
            }

            if ($dto->accountType->requiresShippingCompanyProfile()) {
                ShippingCompany::query()->create([
                    'user_id' => $user->user_id,
                    'company_name' => $dto->profile['company_name'] ?? $dto->name,
                    'commercial_reg' => $dto->profile['commercial_reg'] ?? null,
                    'logo_url' => $dto->profile['logo_url'] ?? null,
                    'commission_type' => $dto->profile['commission_type'] ?? 1,
                    'commission_value' => $dto->profile['commission_value'] ?? 0,
                ]);
            }

            if ($dto->accountType->requiresDeliveryAgentProfile()) {
                DeliveryAgent::query()->create([
                    'user_id' => $user->user_id,
                    'supervisor_agent_id' => $dto->profile['supervisor_agent_id'] ?? null,
                    'national_id' => $dto->profile['national_id'] ?? null,
                    'vehicle_type' => $dto->profile['vehicle_type'] ?? null,
                    'vehicle_plate_number' => $dto->profile['vehicle_plate_number'] ?? null,
                    'commission_type' => $dto->profile['commission_type'] ?? 1,
                    'commission_value' => $dto->profile['commission_value'] ?? 0,
                ]);
            }

            return $user->load(['shippingCompany', 'deliveryAgent', 'staffMember']);
        });
    }

    /**
     * @param  array<int, string>  $roles
     */
    protected function validateRolesExist(array $roles): void
    {
        foreach ($roles as $roleName) {
            if (! Role::query()->where('name', $roleName)->where('guard_name', 'api')->exists()) {
                throw new RoleNotFoundException();
            }
        }
    }
}
