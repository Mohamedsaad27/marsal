<?php

return [
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'phone' => 'phone number',
        'password' => 'password',
        'account_type' => 'account type',
        'roles' => 'roles',
        'roles.*' => 'role',
        'profile' => 'profile data',
        'profile.company_name' => 'company name',
        'profile.commercial_reg' => 'commercial registration',
        'profile.national_id' => 'national ID',
        'profile.vehicle_type' => 'vehicle type',
        'profile.vehicle_plate_number' => 'vehicle plate number',
    ],

    'messages' => [
        'name.required' => 'Name is required.',
        'name.string' => 'Name must be text.',
        'name.max' => 'Name is too long (maximum 255 characters).',

        'email.required' => 'Email address is required.',
        'email.email' => 'Please enter a valid email address.',
        'email.max' => 'Email is too long (maximum 255 characters).',
        'email.unique' => 'This email address is already in use.',

        'phone.required' => 'Phone number is required.',
        'phone.string' => 'Phone number must be text.',
        'phone.max' => 'Phone number is too long (maximum 20 characters).',
        'phone.unique' => 'This phone number is already in use.',

        'password.required' => 'Password is required.',
        'password.string' => 'Password must be text.',
        'password.min' => 'Password must be at least 8 characters.',

        'account_type.required' => 'Account type is required.',
        'account_type.string' => 'Account type is invalid.',
        'account_type.in' => 'Account type must be super admin, staff member, shipping company, or delivery agent.',

        'roles.required' => 'At least one role is required.',
        'roles.array' => 'Roles must be sent as a list.',
        'roles.min' => 'At least one role is required.',
        'roles.*.required' => 'Each role entry is required.',
        'roles.*.string' => 'Each role must be text.',
        'roles.*.max' => 'Role name is too long (maximum 100 characters).',

        'profile.array' => 'Profile data must be an object.',

        'profile.company_name.required' => 'Company name is required for a shipping company account.',
        'profile.company_name.required_if' => 'Company name is required when creating a shipping company account.',
        'profile.company_name.string' => 'Company name must be text.',
        'profile.company_name.max' => 'Company name is too long (maximum 200 characters).',

        'profile.commercial_reg.string' => 'Commercial registration must be text.',
        'profile.commercial_reg.max' => 'Commercial registration is too long (maximum 100 characters).',

        'profile.national_id.string' => 'National ID must be text.',
        'profile.national_id.max' => 'National ID is too long (maximum 20 characters).',
        'profile.national_id.unique' => 'This national ID is already registered to another agent.',

        'profile.vehicle_type.integer' => 'Vehicle type must be a number.',
        'profile.vehicle_type.min' => 'Vehicle type is invalid.',
        'profile.vehicle_type.max' => 'Vehicle type is invalid.',

        'profile.vehicle_plate_number.string' => 'Plate number must be text.',
        'profile.vehicle_plate_number.max' => 'Plate number is too long (maximum 30 characters).',
    ],
];
