<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';
$container = require __DIR__ . '/container.php';

$config = [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\presentation\controllers',
    'viewPath' => '@app/presentation/views',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU',
    'container' => $container,
    'components' => [
        'db' => $db,
        'mutex' => [
            'class' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? \yii\mutex\PgsqlMutex::class
                : \yii\mutex\MysqlMutex::class,
        ],
        'queue' => [
            'class' => \app\infrastructure\queue\HandlerAwareQueue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
            'mutex' => env('DB_DRIVER', 'mysql') === 'pgsql'
                ? \yii\mutex\PgsqlMutex::class
                : \yii\mutex\MysqlMutex::class,
        ],
        'cache' => [
            'class' => 'yii\caching\DummyCache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/presentation/mail',
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
            'identityClass' => 'app\infrastructure\persistence\User',
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
        ],
    ],
    'params' => $params,
];

return $config;
