<?php

return [
    'attributes' => [
        'name' => 'role name',
        'permissions' => 'permissions',
        'permissions.*' => 'permission',
    ],

    'messages' => [
        'name.required' => 'Role name is required.',
        'name.string' => 'Role name must be text.',
        'name.max' => 'Role name is too long (maximum 100 characters).',
        'name.unique' => 'This role name is already in use.',

        'permissions.required' => 'Permissions list is required.',
        'permissions.array' => 'Permissions must be sent as a list.',
        'permissions.*.required' => 'Each permission is required.',
        'permissions.*.string' => 'Each permission must be text.',
        'permissions.*.max' => 'Permission name is too long (maximum 100 characters).',
    ],
];
