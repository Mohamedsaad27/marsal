<?php

return [
    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'account_type' => 'نوع الحساب',
        'type' => 'نوع الحساب',
        'role' => 'الدور',
        'roles' => 'الأدوار',
        'roles.*' => 'الدور',
        'profile' => 'بيانات الملف',
        'profile.company_name' => 'اسم الشركة',
        'profile.commercial_reg' => 'السجل التجاري',
        'profile.national_id' => 'الرقم القومي',
        'profile.vehicle_type' => 'نوع المركبة',
        'profile.vehicle_plate_number' => 'رقم لوحة المركبة',
        'gender' => 'الجنس',
        'avatar' => 'الصورة الشخصية',
        'address' => 'العنوان',
        'address.city_id' => 'المدينة',
        'address.address_line' => 'سطر العنوان',
        'address.landmark' => 'علامة مميزة',
        'address.street' => 'الشارع',
        'address.building_number' => 'رقم المبنى',
        'address.floor_number' => 'رقم الطابق',
        'address.apartment_number' => 'رقم الشقة',
        'address.is_default' => 'العنوان الافتراضي',
    ],

    'messages' => [
        'name.required' => 'الاسم مطلوب.',
        'name.string' => 'يجب أن يكون الاسم نصاً.',
        'name.max' => 'الاسم طويل جداً (الحد الأقصى 255 حرفاً).',

        'email.required' => 'البريد الإلكتروني مطلوب.',
        'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
        'email.max' => 'البريد الإلكتروني طويل جداً (الحد الأقصى 255 حرفاً).',
        'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',

        'phone.required' => 'رقم الهاتف مطلوب.',
        'phone.string' => 'يجب أن يكون رقم الهاتف نصاً.',
        'phone.max' => 'رقم الهاتف طويل جداً (الحد الأقصى 20 رقماً).',
        'phone.unique' => 'رقم الهاتف هذا مستخدم بالفعل.',

        'password.required' => 'كلمة المرور مطلوبة.',
        'password.string' => 'يجب أن تكون كلمة المرور نصاً.',
        'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',

        'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب.',
        'password_confirmation.string' => 'يجب أن يكون تأكيد كلمة المرور نصاً.',

        'account_type.required' => 'نوع الحساب مطلوب.',
        'account_type.string' => 'نوع الحساب غير صالح.',
        'account_type.in' => 'نوع الحساب يجب أن يكون: مدير نظام، موظف إداري، شركة شحن، أو مندوب توصيل.',

        'type.required' => 'نوع الحساب مطلوب.',
        'type.string' => 'نوع الحساب غير صالح.',
        'type.in' => 'نوع الحساب يجب أن يكون: مدير نظام، موظف إداري، شركة شحن، أو مندوب توصيل.',

        'role.required' => 'الدور مطلوب.',
        'role.string' => 'اسم الدور يجب أن يكون نصاً.',
        'role.max' => 'اسم الدور طويل جداً (الحد الأقصى 100 حرف).',

        'roles.required' => 'يجب تحديد دور واحد على الأقل.',
        'roles.array' => 'الأدوار يجب أن تُرسل كقائمة.',
        'roles.min' => 'يجب تحديد دور واحد على الأقل.',
        'roles.*.required' => 'كل عنصر في الأدوار مطلوب.',
        'roles.*.string' => 'اسم الدور يجب أن يكون نصاً.',
        'roles.*.max' => 'اسم الدور طويل جداً (الحد الأقصى 100 حرف).',

        'profile.array' => 'بيانات الملف يجب أن تكون كائناً.',

        'profile.company_name.required' => 'اسم الشركة مطلوب لحساب شركة الشحن.',
        'profile.company_name.required_if' => 'اسم الشركة مطلوب عند إنشاء حساب شركة شحن.',
        'profile.company_name.string' => 'اسم الشركة يجب أن يكون نصاً.',
        'profile.company_name.max' => 'اسم الشركة طويل جداً (الحد الأقصى 200 حرف).',

        'profile.commercial_reg.string' => 'السجل التجاري يجب أن يكون نصاً.',
        'profile.commercial_reg.max' => 'السجل التجاري طويل جداً (الحد الأقصى 100 حرف).',

        'profile.national_id.string' => 'الرقم القومي يجب أن يكون نصاً.',
        'profile.national_id.max' => 'الرقم القومي طويل جداً (الحد الأقصى 20 رقماً).',
        'profile.national_id.unique' => 'الرقم القومي مسجل لمندوب آخر.',

        'profile.vehicle_type.integer' => 'نوع المركبة يجب أن يكون رقماً.',
        'profile.vehicle_type.min' => 'نوع المركبة غير صالح.',
        'profile.vehicle_type.max' => 'نوع المركبة غير صالح.',

        'profile.vehicle_plate_number.string' => 'رقم اللوحة يجب أن يكون نصاً.',
        'profile.vehicle_plate_number.max' => 'رقم اللوحة طويل جداً (الحد الأقصى 30 حرفاً).',

        'gender.string' => 'يجب أن يكون الجنس نصاً.',
        'gender.in' => 'الجنس يجب أن يكون ذكراً أو أنثى.',

        'avatar.file' => 'الصورة الشخصية يجب أن تكون ملفاً.',
        'avatar.image' => 'الصورة الشخصية يجب أن تكون صورة.',
        'avatar.mimes' => 'صيغة الصورة غير مدعومة. استخدم jpg أو jpeg أو png أو webp.',
        'avatar.max' => 'حجم الصورة يتجاوز الحد المسموح (2 ميجابايت).',

        'address.required' => 'العنوان مطلوب.',
        'address.array' => 'يجب إرسال العنوان ككائن.',

        'address.address_line.required' => 'سطر العنوان مطلوب.',
        'address.address_line.string' => 'سطر العنوان يجب أن يكون نصاً.',
        'address.address_line.max' => 'سطر العنوان طويل جداً (الحد الأقصى 500 حرف).',

        'address.city_id.uuid' => 'معرف المدينة يجب أن يكون UUID صالحاً.',
        'address.city_id.exists' => 'المدينة المحددة غير موجودة.',
    ],
];
