<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

use OpenApi\Generator;

try {
    $openapi = Generator::scan([
        __DIR__ . '/OpenApiSpec.php',
        __DIR__ . '/../../presentation/controllers',
    ]);
    file_put_contents(__DIR__ . '/openapi.yaml', $openapi->toYaml());
    echo "✅ OpenAPI spec generated successfully to docs/api/openapi.yaml\n";
} catch (Throwable $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    exit(1);
}
