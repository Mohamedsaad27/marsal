<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Domain\Enums\PermissionEnum;

class GetGroupedPermissionsUseCase
{
    public function execute(): array
    {
        $grouped = PermissionEnum::groupedBySection();

        return array_values(array_map(
            fn (string $group, array $permissions) => [
                'group' => $group,
                'group_label_ar' => $permissions[0]->groupLabelAr(),
                'permissions' => array_map(
                    fn (PermissionEnum $permission) => [
                        'name' => $permission->value,
                        'label_ar' => $permission->labelAr(),
                        'label_en' => $permission->labelEn(),
                    ],
                    $permissions
                ),
            ],
            array_keys($grouped),
            $grouped
        ));
    }
}
