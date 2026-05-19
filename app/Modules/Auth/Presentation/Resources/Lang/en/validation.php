<?php

return [
    'attributes' => [
        'identifier' => 'email or phone number',
        'password' => 'password',
    ],

    'messages' => [
        'identifier.required' => 'Email or phone number is required.',
        'identifier.string' => 'Login identifier must be text.',
        'identifier.max' => 'Login identifier is too long (maximum 255 characters).',

        'password.required' => 'Password is required.',
        'password.string' => 'Password must be text.',
        'password.min' => 'Password must be at least 6 characters.',
    ],
];
