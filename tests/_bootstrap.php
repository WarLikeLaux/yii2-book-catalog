<?php

declare(strict_types=1);

use DG\BypassFinals;
use Dotenv\Dotenv;

define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

if (class_exists(BypassFinals::class)) {
    BypassFinals::enable();
}

require __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__ . '/_support/DbCleaner.php';
require_once __DIR__ . '/_support/IdempotencyStreamStub.php';
