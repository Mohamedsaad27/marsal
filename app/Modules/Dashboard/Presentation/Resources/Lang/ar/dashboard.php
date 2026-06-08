<?php

return [
    'summary_fetched' => 'تم جلب ملخص لوحة التحكم بنجاح',
    'shipments_chart_fetched' => 'تم جلب بيانات مخطط الشحنات بنجاح',
    'top_agents_fetched' => 'تم جلب أفضل المندوبين بنجاح',
    'collections_balance_fetched' => 'تم جلب رصيد التحصيلات المعلقة بنجاح',
    'delivery_performance_fetched' => 'تم جلب إحصائيات أداء التوصيل بنجاح',
    'avg_delivery_time_fetched' => 'تم جلب متوسط وقت التوصيل بنجاح',
    'recent_orders_fetched' => 'تم جلب الطلبات الأخيرة بنجاح',

    'in_delivery_label' => 'نشطة الآن',
    'currency' => 'ج.م',

    'comparison_improvement' => 'أسرع بـ :percent% من الأسبوع الماضي',
    'comparison_regression' => 'أبطأ بـ :percent% من الأسبوع الماضي',
    'comparison_unchanged' => 'نفس متوسط الأسبوع الماضي',

    'status' => [
        'pending' => 'قيد الانتظار',
        'in_delivery' => 'قيد التوصيل',
        'delivered' => 'تم التوصيل',
        'postponed' => 'مؤجل',
        'failed' => 'فشل التوصيل',
        'rejected' => 'مرفوض',
    ],

    // Carbon dayOfWeek: 0=Sunday … 6=Saturday — chart runs Saturday → Friday
    'weekdays' => [
        6 => 'السبت',
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
    ],
];
