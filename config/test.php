<?php

declare(strict_types=1);

use app\infrastructure\components\AppMysqlMutex;
use app\infrastructure\components\AppPgsqlMutex;
use app\infrastructure\components\AppRedisConnection;
use app\infrastructure\persistence\User;
use app\infrastructure\queue\HandlerAwareQueue;
use yii\mutex\MysqlMutex;
use yii\mutex\PgsqlMutex;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';
$container = require __DIR__ . '/container.php';

$config = [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\presentation\controllers',
    'viewPath' => '@app/src/presentation/views',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU',
    'sourceLanguage' => 'en-US',
    'container' => $container($params),
    'components' => [
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
            ],
        ],
        'db' => $db,
        'redis' => [
            'class' => AppRedisConnection::class,
            'hostname' => env('REDIS_HOST', 'localhost'),
            'port' => (int)env('REDIS_PORT', 6379),
            'database' => 15,
        ],
        'mutex' => [
            'class' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? AppPgsqlMutex::class
                : AppMysqlMutex::class,
        ],
        'queue' => [
            'class' => HandlerAwareQueue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
            'mutex' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? PgsqlMutex::class
                : MysqlMutex::class,
        ],
        'cache' => [
            'class' => 'yii\caching\DummyCache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/src/presentation/mail',
            'useFileTransport' => true,
            'messageClass' => 'yii\symfonymailer\Message',
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'user' => [
            'identityClass' => User::class,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
    ],
    'params' => $params,
];

return $config;
