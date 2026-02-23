<?php

declare(strict_types=1);

use app\infrastructure\components\AppMysqlMutex;
use app\infrastructure\components\AppPgsqlMutex;
use app\infrastructure\components\AppRedisConnection;
use app\infrastructure\queue\HandlerAwareQueue;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'redis' => [
            'class' => AppRedisConnection::class,
            'hostname' => env('REDIS_HOST', 'redis'),
            'port' => (int)env('REDIS_PORT', '6379'),
            'database' => 0,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
        'log' => [
            'targets' => array_filter([
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['sms'],
                    'levels' => ['info', 'error'],
                    'logFile' => '@runtime/logs/sms.log',
                    'logVars' => [],
                ],
            ]),
        ],
        'db' => $db,
        'mutex' => [
            'class' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? AppPgsqlMutex::class
                : AppMysqlMutex::class,
            'db' => $db,
        ],
        'queue' => [
            'class' => HandlerAwareQueue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
        ],
    ],
    'controllerMap' => [
        'queue' => \yii\queue\db\Command::class,
    ],
    'container' => (require __DIR__ . '/container.php')($params),
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];

    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
}

return $config;
