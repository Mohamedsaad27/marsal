<?php

return [
    'name'           => 'Notifications',
    'alias'          => 'Notifications',

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM)
    |--------------------------------------------------------------------------
    |
    | Preferred: FIREBASE_CREDENTIALS — path to the service-account JSON file
    |   e.g. storage/app/firebase-credentials.json
    |   Uses FCM HTTP v1 API (OAuth2).
    |
    | Fallback: FCM_SERVER_KEY — legacy server key (deprecated by Google).
    */
    'fcm_server_key'       => env('FCM_SERVER_KEY', ''),
    'firebase_credentials' => env('FIREBASE_CREDENTIALS', ''),
];