<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5',
    'appPort' => (int)env('APP_PORT', 8000),
    'swaggerPort' => (int)env('SWAGGER_PORT', 8081),
    'buggregatorUiPort' => (int)env('BUGGREGATOR_UI_PORT', 8090),
    'storage' => [
        'basePath' => '@app/web/uploads',
        'baseUrl' => '/uploads',
        'placeholderUrl' => 'https://picsum.photos/seed/{seed}/400/600',
    ],
    'idempotency' => [
        'ttl' => (int)env('IDEMPOTENCY_TTL', 86400),
        'lockTimeout' => (int)env('IDEMPOTENCY_LOCK_TIMEOUT', 1),
        'waitSeconds' => (int)env('IDEMPOTENCY_WAIT_SECONDS', 1),
        'smsPhoneHashKey' => (string)env('SMS_IDEMPOTENCY_HASH_KEY', 'changeme'),
    ],
    'rateLimit' => [
        'limit' => (int)env('RATE_LIMIT_REQUESTS', 60),
        'window' => (int)env('RATE_LIMIT_WINDOW', 60),
    ],
    'reports' => [
        'cacheTtl' => (int)env('REPORTS_CACHE_TTL', 3600),
    ],
    'shell' => [
        'aliasTargets' => [
            'infrastructure/persistence' => 1,
            'domain' => 2,
            'application' => 3,
        ],
    ],
    'buggregator' => [
        'log' => [
            'host' => (string)env('BUGGREGATOR_LOG_HOST', 'buggregator'),
            'port' => (int)env('BUGGREGATOR_LOG_PORT', 9913),
        ],
        'inspector' => [
            'url' => (string)env('INSPECTOR_URL', 'http://buggregator:8000'),
            'ingestionKey' => (string)env('INSPECTOR_INGESTION_KEY', 'buggregator'),
        ],
    ],
];
