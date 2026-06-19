<?php

return [
    'name'           => 'Notifications',
    'alias'          => 'Notifications',

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM)
    |--------------------------------------------------------------------------
    | FCM_SERVER_KEY  — your Firebase Server Key (legacy HTTP API)
    | Add FCM_SERVER_KEY=<your-key> to your .env file.
    */
    'fcm_server_key' => env('FCM_SERVER_KEY', ''),
    'firebase_credentials' => env('FIREBASE_CREDENTIALS', ''),
];