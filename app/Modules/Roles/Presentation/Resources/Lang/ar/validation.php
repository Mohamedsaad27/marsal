<?php

return [
    'attributes' => [
        'name' => 'اسم الدور',
        'permissions' => 'الصلاحيات',
        'permissions.*' => 'الصلاحية',
    ],

    'messages' => [
        'name.required' => 'اسم الدور مطلوب.',
        'name.string' => 'اسم الدور يجب أن يكون نصاً.',
        'name.max' => 'اسم الدور طويل جداً (الحد الأقصى 100 حرف).',
        'name.unique' => 'اسم الدور مستخدم بالفعل.',

        'permissions.required' => 'قائمة الصلاحيات مطلوبة.',
        'permissions.array' => 'الصلاحيات يجب أن تُرسل كقائمة.',
        'permissions.*.required' => 'كل صلاحية مطلوبة.',
        'permissions.*.string' => 'اسم الصلاحية يجب أن يكون نصاً.',
        'permissions.*.max' => 'اسم الصلاحية طويل جداً (الحد الأقصى 100 حرف).',
    ],
];
