<?php

return [
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'phone' => 'phone number',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'account_type' => 'account type',
        'roles' => 'roles',
        'roles.*' => 'role',
        'profile' => 'profile data',
        'profile.company_name' => 'company name',
        'profile.commercial_reg' => 'commercial registration',
        'profile.national_id' => 'national ID',
        'profile.vehicle_type' => 'vehicle type',
        'profile.vehicle_plate_number' => 'vehicle plate number',
        'gender' => 'gender',
        'avatar' => 'profile photo',
        'address' => 'address',
        'address.city_id' => 'city',
        'address.address_line' => 'address line',
        'address.landmark' => 'landmark',
        'address.street' => 'street',
        'address.building_number' => 'building number',
        'address.floor_number' => 'floor number',
        'address.apartment_number' => 'apartment number',
        'address.is_default' => 'default address flag',
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
        'password.confirmed' => 'Password confirmation does not match.',

        'password_confirmation.required' => 'Password confirmation is required.',
        'password_confirmation.string' => 'Password confirmation must be text.',

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

        'gender.string' => 'Gender must be text.',
        'gender.in' => 'Gender must be male or female.',

        'avatar.file' => 'Profile photo must be a file.',
        'avatar.image' => 'Profile photo must be an image.',
        'avatar.mimes' => 'Unsupported image format. Use jpg, jpeg, png, or webp.',
        'avatar.max' => 'Image size exceeds the allowed limit (2 MB).',

        'address.required' => 'Address is required.',
        'address.array' => 'Address must be sent as an object.',

        'address.address_line.required' => 'Address line is required.',
        'address.address_line.string' => 'Address line must be text.',
        'address.address_line.max' => 'Address line is too long (maximum 500 characters).',

        'address.city_id.uuid' => 'City ID must be a valid UUID.',
        'address.city_id.exists' => 'The selected city does not exist.',
    ],
];
