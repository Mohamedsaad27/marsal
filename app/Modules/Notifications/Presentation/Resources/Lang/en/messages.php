<?php

return [
    // API response messages
    'list_success'         => 'Notifications retrieved successfully',
    'unread_count_success' => 'Unread notification count retrieved successfully',
    'marked_read'          => 'Notification marked as read',
    'all_marked_read'      => 'All notifications marked as read',
    'not_found'            => 'Notification not found or does not belong to you',

    // Notification titles (for reference — actual titles come from NotificationTemplateService)
    'titles' => [
        'new_order'          => 'New Delivery Order',
        'status_change'      => 'Order Status Updated',
        'approval_request'   => 'Price Change Approval Request',
        'timer_start'        => 'Refusal Timer Started',
        'timer_expired'      => 'Refusal Timer Expired',
        'new_message'        => 'New Message',
        'phone_updated'      => 'Phone Number Updated',
        'postponed_reminder' => 'Postponed Delivery Reminder',
    ],
];