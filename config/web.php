<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' => 'Yii 2 Book Catalog',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\presentation\controllers',
    'viewPath' => '@app/presentation/views',
    'language' => 'ru-RU',
    'sourceLanguage' => 'en-US',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => env('COOKIE_VALIDATION_KEY', ''),
        ],
        'response' => [
            'on beforeSend' => function ($event) {
                $event->sender->headers->add('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");
                $event->sender->headers->add('X-Frame-Options', 'SAMEORIGIN');
                $event->sender->headers->add('X-Content-Type-Options', 'nosniff');
                $event->sender->headers->add('Referrer-Policy', 'strict-origin-when-cross-origin');
            },
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => env('REDIS_HOST', 'redis'),
            'port' => (int)env('REDIS_PORT', '6379'),
            'database' => 0,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
        'assetManager' => [
            'bundles' => [
                'kartik\base\WidgetAsset' => ['bsVersion' => '5'],
                'kartik\select2\Select2Asset' => ['bsVersion' => '5'],
            ],
        ],
        'user' => [
            'identityClass' => 'app\infrastructure\persistence\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/presentation/mail',
            'useFileTransport' => env('MAILER_USE_FILE_TRANSPORT', true),
            'transport' => [
                'scheme' => 'smtp',
                'host' => env('MAILER_HOST', '127.0.0.1'),
                'port' => (int)env('MAILER_PORT', 1025),
                'dsn' => 'native://default',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
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
                    'except' => ['yii\web\HttpException:404'],
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
            'class' => \yii\mutex\MysqlMutex::class,
            'db' => $db,
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'api/books' => 'api/book/index',
            ],
        ],
    ],
    'container' => require __DIR__ . '/container.php',
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
