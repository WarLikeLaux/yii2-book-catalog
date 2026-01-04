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
        'tempBasePath' => '@app/web/uploads/temp',
        'tempBaseUrl' => '/uploads/temp',
    ],
    'idempotency' => [
        'ttl' => (int)env('IDEMPOTENCY_TTL', 86400),
        'lockTimeout' => (int)env('IDEMPOTENCY_LOCK_TIMEOUT', 1),
        'waitSeconds' => (int)env('IDEMPOTENCY_WAIT_SECONDS', 1),
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
];
