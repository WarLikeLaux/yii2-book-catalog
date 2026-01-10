<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

declare(strict_types=1);

use Dotenv\Dotenv;

$baseDir = dirname(__DIR__);
$dotenv = Dotenv::createImmutable($baseDir);
$dotenv->safeLoad();

$dotenv->required('DB_DRIVER')->allowedValues(['mysql', 'pgsql']);
$dotenv->required('YII_ENV')->allowedValues(['dev', 'prod', 'test']);
$dotenv->required('YII_DEBUG')->isBoolean();
$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

if (env('YII_ENV') === 'prod') {
    $dotenv->required('SMS_API_KEY')->notEmpty();
    $dotenv->required('SMS_IDEMPOTENCY_HASH_KEY')->notEmpty();
}

defined('YII_DEBUG') || define('YII_DEBUG', env('YII_DEBUG'));
defined('YII_ENV') || define('YII_ENV', env('YII_ENV'));
