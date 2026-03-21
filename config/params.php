<?php

declare(strict_types=1);

use Env\Env;

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5',
    'appPort' => Env::get('APP_PORT') ?? 8000,
    'swaggerPort' => Env::get('SWAGGER_PORT') ?? 8081,
    'storage' => [
        'basePath' => '@app/web/uploads',
        'baseUrl' => '/uploads',
        'placeholderUrl' => 'https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+{seed}',
    ],
    'idempotency' => [
        'ttl' => Env::get('IDEMPOTENCY_TTL') ?? 86400,
        'lockTimeout' => Env::get('IDEMPOTENCY_LOCK_TIMEOUT') ?? 1,
        'waitSeconds' => Env::get('IDEMPOTENCY_WAIT_SECONDS') ?? 1,
        'smsPhoneHashKey' => Env::get('SMS_IDEMPOTENCY_HASH_KEY') ?? 'changeme',
    ],
    'rateLimit' => [
        'limit' => Env::get('RATE_LIMIT_REQUESTS') ?? 60,
        'window' => Env::get('RATE_LIMIT_WINDOW') ?? 60,
    ],
    'reports' => [
        'cacheTtl' => Env::get('REPORTS_CACHE_TTL') ?? 3600,
    ],
    'shell' => [
        'aliasTargets' => [
            'infrastructure/persistence' => 1,
            'domain' => 2,
            'application' => 3,
        ],
    ],
    'health' => [
        'disk' => [
            'thresholdGb' => Env::get('HEALTH_DISK_THRESHOLD_GB') ?? 10.0,
        ],
        'version' => '1.0.0',
    ],
];
