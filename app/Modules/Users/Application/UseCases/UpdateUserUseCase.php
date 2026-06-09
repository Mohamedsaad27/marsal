<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Auth\Application\Exceptions\RoleNotFoundException;
use App\Modules\Locations\Infrastructure\Database\Models\Address;
use App\Modules\Users\Application\DTOs\UpdateUserDTO;
use App\Modules\Users\Application\Exceptions\UserActionForbiddenException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly GetUserUseCase $getUserUseCase,
    ) {}

    public function execute(UpdateUserDTO $dto): User
    {
        $user = $this->getUserUseCase->execute($dto->userId);

        if ($dto->roles !== null) {
            $this->validateRolesExist($dto->roles);
            $this->guardLastSuperAdminRoleChange($user, $dto->roles);
        }

        return DB::transaction(function () use ($user, $dto) {
            $attributes = $dto->userAttributes();

            if ($attributes !== []) {
                $this->repository->update($user, $attributes);
                $user->refresh();
            }

            if ($dto->roles !== null) {
                $user->syncRoles($dto->roles);
            }

            if ($dto->profile !== null) {
                $this->updateLinkedProfile($user, $dto->profile);
            }

            if (in_array('address', $dto->presentKeys, true) && $dto->address !== null) {
                $this->upsertUserAddress($user, $dto->address);
            }

            return $user->fresh()->load(['roles', 'deliveryAgent', 'shippingCompany', 'staffMember.department', 'addresses.city']);
        });
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function upsertUserAddress(User $user, array $address): void
    {
        $payload = [
            'city_id' => $address['city_id'] ?? null,
            'address_line' => $address['address_line'],
            'landmark' => $address['landmark'] ?? null,
            'street' => $address['street'] ?? null,
            'building_number' => $address['building_number'] ?? null,
            'floor_number' => $address['floor_number'] ?? null,
            'apartment_number' => $address['apartment_number'] ?? null,
            'is_default' => $address['is_default'] ?? true,
        ];

        $existing = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();

        if ($existing) {
            $existing->update($payload);

            return;
        }

        Address::query()->create(array_merge($payload, ['user_id' => $user->user_id]));
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function updateLinkedProfile(User $user, array $profile): void
    {
        if ($user->staffMember) {
            $updates = array_intersect_key($profile, array_flip(['department_id', 'job_title', 'notes']));
            if ($updates !== []) {
                $user->staffMember->update($updates);
            }
        }

        if ($user->shippingCompany) {
            $updates = array_intersect_key($profile, array_flip(['company_name', 'commercial_reg', 'logo_url']));
            if ($updates !== []) {
                $user->shippingCompany->update($updates);
            }
        }

        if ($user->deliveryAgent) {
            $updates = array_intersect_key($profile, array_flip([
                'national_id',
                'vehicle_type',
                'vehicle_plate_number',
                'supervisor_agent_id',
            ]));
            if ($updates !== []) {
                $user->deliveryAgent->update($updates);
            }
        }
    }

    /**
     * @param  list<string>  $roles
     */
    private function validateRolesExist(array $roles): void
    {
        foreach ($roles as $roleName) {
            if (! Role::query()->where('name', $roleName)->where('guard_name', 'api')->exists()) {
                throw new RoleNotFoundException;
            }
        }
    }

    /**
     * @param  list<string>  $newRoles
     */
    private function guardLastSuperAdminRoleChange(User $user, array $newRoles): void
    {
        if (! $user->hasRole('super_admin')) {
            return;
        }

        if (in_array('super_admin', $newRoles, true)) {
            return;
        }

        if ($this->repository->countActiveUsersWithRole('super_admin') <= 1) {
            throw new UserActionForbiddenException(__('users::messages.cannot_remove_last_super_admin'));
        }
    }
}
