<?php

declare(strict_types=1);

if (YII_ENV_DEV) {
    $_SERVER['TRAP_SERVER'] = env('TRAP_SERVER', 'buggregator:9912');
}
