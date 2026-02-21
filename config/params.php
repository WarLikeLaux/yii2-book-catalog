<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5',
    'appPort' => (int)env('APP_PORT', 8000),
    'swaggerPort' => (int)env('SWAGGER_PORT', 8081),
    'jaegerUiPort' => (int)env('JAEGER_UI_PORT', 16686),
    'storage' => [
        'basePath' => '@app/web/uploads',
        'baseUrl' => '/uploads',
        'placeholderUrl' => 'https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+{seed}',
    ],
    'idempotency' => [
        'ttl' => (int)env('IDEMPOTENCY_TTL', 86400),
        'lockTimeout' => (int)env('IDEMPOTENCY_LOCK_TIMEOUT', 1),
        'waitSeconds' => (int)env('IDEMPOTENCY_WAIT_SECONDS', 1),
        'smsPhoneHashKey' => env('SMS_IDEMPOTENCY_HASH_KEY', 'changeme'),
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
    'jaeger' => [
        'endpoint' => (string)env('JAEGER_ENDPOINT', 'http://jaeger:4318/v1/traces'),
        'serviceName' => 'yii2-book-catalog',
    ],
    'health' => [
        'disk' => [
            'threshold_gb' => (float)env('HEALTH_DISK_THRESHOLD_GB', 10.0),
        ],
        'version' => '1.0.0',
    ],
];
