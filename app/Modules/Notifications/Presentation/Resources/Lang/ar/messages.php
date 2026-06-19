<?php

return [
    // API response messages
    'list_success'        => 'تم جلب الإشعارات بنجاح',
    'unread_count_success'=> 'تم جلب عدد الإشعارات غير المقروءة بنجاح',
    'marked_read'         => 'تم تحديد الإشعار كمقروء',
    'all_marked_read'     => 'تم تحديد جميع الإشعارات كمقروءة',
    'not_found'           => 'الإشعار غير موجود أو لا يخصك',

    // Notification titles (for reference — actual titles come from NotificationTemplateService)
    'titles' => [
        'new_order'          => 'طلب توصيل جديد',
        'status_change'      => 'تحديث حالة الطلب',
        'approval_request'   => 'طلب موافقة على تغيير السعر',
        'timer_start'        => 'بدأ توقيت رفض الاستلام',
        'timer_expired'      => 'انتهى وقت رفض الاستلام',
        'new_message'        => 'رسالة جديدة',
        'phone_updated'      => 'تم تحديث رقم الهاتف',
        'postponed_reminder' => 'تذكير بموعد تأجيل التسليم',
    ],
];