<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

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
            'class' => 'yii\redis\Connection',
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
                YII_ENV_DEV ? [
                    'class' => 'app\infrastructure\services\BuggregatorLogTarget',
                    'host' => env('BUGGREGATOR_LOG_HOST', 'buggregator'),
                    'port' => (int)env('BUGGREGATOR_LOG_PORT', 9913),
                    'levels' => ['error', 'warning'],
                ] : null,
                YII_ENV_DEV ? [
                    'class' => 'app\infrastructure\services\BuggregatorLogTarget',
                    'host' => env('BUGGREGATOR_LOG_HOST', 'buggregator'),
                    'port' => (int)env('BUGGREGATOR_LOG_PORT', 9913),
                    'levels' => ['info'],
                    'categories' => ['sms', 'application'],
                    // Не дампить $_SERVER и прочее в инфо-логах
                    'logVars' => [],
                ] : null,
            ]),
        ],
        'db' => $db,
        'mutex' => [
            'class' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? \yii\mutex\PgsqlMutex::class
                : \yii\mutex\MysqlMutex::class,
            'db' => $db,
        ],
        'queue' => [
            'class' => \app\infrastructure\queue\HandlerAwareQueue::class,
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
