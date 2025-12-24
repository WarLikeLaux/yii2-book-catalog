<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';
$container = require __DIR__ . '/container.php';

/**
 * Application configuration shared by all test types
 */
$config = [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\presentation\controllers',
    'viewPath' => '@app/presentation/views',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'en-US',
    'container' => $container,
    'components' => [
        'db' => $db,
        'mutex' => [
            'class' => \yii\mutex\MysqlMutex::class,
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => $db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
            'mutex' => \yii\mutex\MysqlMutex::class,
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/presentation/mail',
            // send all mails to a file by default.
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
            // but if you absolutely need it set cookie domain to localhost
            /*
            'csrfCookie' => [
                'domain' => 'localhost',
            ],
            */
        ],
    ],
    'params' => $params,
];

return $config;
