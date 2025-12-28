<?php

declare(strict_types=1);

// Интеграция с Buggregator Trap.
// Подключается в web/index.php и yii (console).

if (YII_ENV_DEV) {
    $_SERVER['TRAP_SERVER'] = env('TRAP_SERVER', 'buggregator:9912');
}
