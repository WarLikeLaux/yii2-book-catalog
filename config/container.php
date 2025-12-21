<?php

declare(strict_types=1);

use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\reports\queries\ReportQueryService;
use app\interfaces\FileStorageInterface;
use app\interfaces\NotificationInterface;
use app\interfaces\SmsSenderInterface;
use app\repositories\AuthorReadRepository;
use app\repositories\BookReadRepository;
use app\services\notifications\FlashNotificationService;
use app\services\sms\SmsPilotSender;
use app\services\storage\LocalFileStorage;
use app\services\YiiPsrLogger;
use app\validators\AuthorExistsValidator;
use app\validators\UniqueFioValidator;
use app\validators\UniqueIsbnValidator;
use Psr\Log\LoggerInterface;

return [
    'definitions' => [
        SmsSenderInterface::class => static fn() => new SmsPilotSender(
            (string)env('SMS_API_KEY', 'MOCK_KEY'),
            new YiiPsrLogger('sms')
        ),
        LoggerInterface::class => static fn() => new YiiPsrLogger('application'),
        FileStorageInterface::class => static fn() => new LocalFileStorage(
            '@app/web/uploads',
            '/uploads'
        ),
        NotificationInterface::class => FlashNotificationService::class,
        UniqueFioValidator::class => static fn($container, $params, $config) => new UniqueFioValidator(
            $container->get(AuthorReadRepository::class),
            $config
        ),
        UniqueIsbnValidator::class => static fn($container, $params, $config) => new UniqueIsbnValidator(
            $container->get(BookReadRepository::class),
            $config
        ),
        AuthorExistsValidator::class => static fn($container, $params, $config) => new AuthorExistsValidator(
            $container->get(AuthorReadRepository::class),
            $config
        ),
    ],
    'singletons' => [
        CreateBookUseCase::class => static fn($container) => new CreateBookUseCase(
            Yii::$app->get('db'),
            Yii::$app->get('queue'),
            $container->get(FileStorageInterface::class)
        ),
        UpdateBookUseCase::class => static fn($container) => new UpdateBookUseCase(
            Yii::$app->get('db'),
            $container->get(FileStorageInterface::class)
        ),
        ReportQueryService::class => static fn() => new ReportQueryService(
            Yii::$app->get('db')
        ),
    ],
];
