<?php

declare(strict_types=1);

// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

use app\infrastructure\runtime\FrankenWorker;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', env('YII_DEBUG', false));
defined('YII_ENV') or define('YII_ENV', env('YII_ENV', 'prod'));

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

require __DIR__ . '/../config/bootstrap.php';

$config = require __DIR__ . '/../config/web.php';

$maxRequests = (int) ($_SERVER['FRANKEN_MAX_REQUESTS'] ?? 1000);

$worker = new FrankenWorker($config, $maxRequests);

exit($worker->run());
