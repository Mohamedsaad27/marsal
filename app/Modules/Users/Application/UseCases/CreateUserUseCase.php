<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Address;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\StaffMember;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Modules\Auth\Application\Exceptions\RoleNotFoundException;
use App\Modules\Auth\Application\UseCases\SendWelcomeEmailUseCase;
use App\Modules\Auth\Application\UseCases\SendWelcomeMessageOnWhatsAppUseCase;

class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SendWelcomeEmailUseCase $sendWelcomeEmailUseCase,
        private readonly SendWelcomeMessageOnWhatsAppUseCase $sendWelcomeMessageOnWhatsAppUseCase,
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
                    'department_id' => $dto->profile['department_id'] ?? null,
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

            if ($dto->address !== []) {
                Address::query()->create([
                    'user_id' => $user->user_id,
                    'city_id' => $dto->address['city_id'] ?? null,
                    'address_line' => $dto->address['address_line'],
                    'landmark' => $dto->address['landmark'] ?? null,
                    'street' => $dto->address['street'] ?? null,
                    'building_number' => $dto->address['building_number'] ?? null,
                    'floor_number' => $dto->address['floor_number'] ?? null,
                    'apartment_number' => $dto->address['apartment_number'] ?? null,
                    'is_default' => $dto->address['is_default'] ?? true,
                ]);
            }

            $user = $user->load(['shippingCompany', 'deliveryAgent', 'staffMember.department', 'addresses.city']);

            $this->sendWelcomeEmailUseCase->execute($user, $dto->password);
            $this->sendWelcomeMessageOnWhatsAppUseCase->execute($user, $dto->password);

            return $user->fresh()->load(['shippingCompany', 'deliveryAgent', 'staffMember.department', 'addresses.city']);
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
