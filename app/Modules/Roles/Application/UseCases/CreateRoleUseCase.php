<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class CreateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $name): Role
    {
        $role = $this->roleRepository->create($name);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Created,
            auditableType: 'roles',
            auditableId:   (string) $role->id,
            newValues:     [
                'name'       => $role->name,
                'guard_name' => $role->guard_name,
            ],
        );

        return $role;
    }
}
