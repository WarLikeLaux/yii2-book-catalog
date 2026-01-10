<?php

declare(strict_types=1);

use DG\BypassFinals;

define('YII_ENV', 'test');
define('YII_DEBUG', true);

require_once __DIR__ . '/../vendor/autoload.php';

if (class_exists(BypassFinals::class)) {
    BypassFinals::enable();
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/_support/DbCleaner.php';
require_once __DIR__ . '/_support/IdempotencyStreamStub.php';
