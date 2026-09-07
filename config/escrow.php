<?php

return [

    'currency' => 'KZT',

    'access_token_ttl' => (int) env('ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => (int) env('REFRESH_TOKEN_TTL', 60 * 60 * 24 * 30),

    'password_min' => 8,
    'reset_code_ttl_minutes' => 15,

    'pagination' => [
        'default' => 15,
        'max' => 50,
    ],

    'commission_rate_bps' => (int) env('ESCROW_COMMISSION_RATE_BPS', 0),

    'auto_start_work_on_reserve' => (bool) env('ESCROW_AUTO_START_WORK', true),

    'documents' => [
        'disk' => env('DOCUMENTS_DISK', 'documents'),
        'max_kb' => 20480,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip', 'txt'],
        'download_ttl_minutes' => 5,
    ],

    'payment' => [
        'default_provider' => env('PAYMENT_PROVIDER', 'sandbox'),
        'sandbox' => [
            'auto_complete' => (bool) env('PAYMENT_SANDBOX_AUTO_COMPLETE', true),
            'webhook_secret' => env('PAYMENT_SANDBOX_WEBHOOK_SECRET', 'sandbox-secret'),
        ],
    ],

];
