<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'test'),

    'bpkad' => [
        'base_url' => env('BPKAD_API_BASE_URL'),
        'client_id' => env('BPKAD_API_CLIENT_ID'),
        'secret' => env('BPKAD_API_SECRET'),
        'callback_secret' => env('BPKAD_CALLBACK_SECRET'),
        'timeout' => (int) env('BPKAD_API_TIMEOUT', 15),
        'endpoints' => [
            'create_va' => env('BPKAD_API_CREATE_VA_PATH', '/api/payments/va'),
            'create_qris' => env('BPKAD_API_CREATE_QRIS_PATH', '/api/payments/qris'),
            'inquiry' => env('BPKAD_API_INQUIRY_PATH', '/api/payments/inquiry'),
            'cancel' => env('BPKAD_API_CANCEL_PATH', '/api/payments/cancel'),
        ],
    ],
];
