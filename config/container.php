<?php

declare(strict_types=1);

use app\interfaces\FileStorageInterface;
use app\interfaces\SmsSenderInterface;
use app\services\AuthorService;
use app\services\BookService;
use app\services\sms\SmsPilotSender;
use app\services\storage\LocalFileStorage;
use app\services\SubscriptionService;
use app\services\YiiPsrLogger;
use Psr\Log\LoggerInterface;

return [
    'definitions' => [
        SmsSenderInterface::class => static fn($container): SmsSenderInterface => new SmsPilotSender(
            (string)env('SMS_API_KEY', 'MOCK_KEY'),
            $container->get(LoggerInterface::class)
        ),
        LoggerInterface::class => static fn(): LoggerInterface => new YiiPsrLogger('sms'),
        FileStorageInterface::class => static fn($container, $params, $config): FileStorageInterface => new LocalFileStorage(
            '@app/web/uploads',
            '/uploads'
        ),
    ],
    'singletons' => [
        BookService::class => static fn($container, $params, $config): BookService => new BookService(
            Yii::$app->get('db'),
            Yii::$app->get('queue'),
            $container->get(FileStorageInterface::class)
        ),
        AuthorService::class => AuthorService::class,
        SubscriptionService::class => SubscriptionService::class,
    ],
];
