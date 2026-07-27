<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'test'),

    'bpkad' => [
        'environment' => env('BPKAD_API_ENVIRONMENT', 'sandbox'),
        'service_key' => env('BPKAD_SERVICE_KEY', 'sigap_gsg'),
        'service_code' => env('BPKAD_SERVICE_CODE', '33'),
        'object_type' => env('BPKAD_OBJECT_TYPE', 'GSG'),
        'base_url' => env('BPKAD_API_BASE_URL'),
        'client_id' => env('BPKAD_API_CLIENT_ID'),
        'secret' => env('BPKAD_API_SECRET'),
        'callback_secret' => env('BPKAD_CALLBACK_SECRET'),
        'timeout' => (int) env('BPKAD_API_TIMEOUT', 15),
        'endpoints' => [
            'create_va' => env('BPKAD_API_CREATE_VA_PATH', '/payments/va'),
            'create_qris' => env('BPKAD_API_CREATE_QRIS_PATH', '/payments/qris'),
            'inquiry' => env('BPKAD_API_INQUIRY_PATH', '/payments/{external_reference}'),
            'cancel' => env('BPKAD_API_CANCEL_PATH', '/payments/{external_reference}/cancel'),
        ],
    ],
];
