<?php

declare(strict_types=1);

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\books\queries\BookQueryService;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\IdempotencyService;
use app\application\common\IdempotencyServiceInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\QueueInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\SmsSenderInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\application\reports\queries\ReportQueryService;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\adapters\YiiQueueAdapter;
use app\infrastructure\adapters\YiiTransactionAdapter;
use app\infrastructure\adapters\YiiTranslatorAdapter;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\IdempotencyRepository;
use app\infrastructure\repositories\ReportRepository;
use app\infrastructure\repositories\SubscriptionRepository;
use app\infrastructure\services\notifications\FlashNotificationService;
use app\infrastructure\services\sms\SmsPilotSender;
use app\infrastructure\services\storage\LocalFileStorage;
use app\infrastructure\services\YiiPsrLogger;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\validators\AuthorExistsValidator;
use app\presentation\validators\UniqueFioValidator;
use app\presentation\validators\UniqueIsbnValidator;
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
        TranslatorInterface::class => YiiTranslatorAdapter::class,
        IdempotencyInterface::class => IdempotencyRepository::class,
        BookRepositoryInterface::class => BookRepository::class,
        AuthorRepositoryInterface::class => AuthorRepository::class,
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        ReportRepositoryInterface::class => static fn($container) => new ReportRepository(
            Yii::$app->get('db')
        ),
        TransactionInterface::class => static fn($container) => new YiiTransactionAdapter(
            Yii::$app->get('db')
        ),
        QueueInterface::class => static fn($container) => new YiiQueueAdapter(
            Yii::$app->get('queue')
        ),
        EventPublisherInterface::class => static fn($container) => new YiiEventPublisherAdapter(
            $container->get(QueueInterface::class)
        ),
        UniqueFioValidator::class => static fn($container, $params, $config) => new UniqueFioValidator(
            $container->get(AuthorRepositoryInterface::class),
            $config
        ),
        UniqueIsbnValidator::class => static fn($container, $params, $config) => new UniqueIsbnValidator(
            $container->get(BookRepositoryInterface::class),
            $config
        ),
        AuthorExistsValidator::class => static fn($container, $params, $config) => new AuthorExistsValidator(
            $container->get(AuthorRepositoryInterface::class),
            $config
        ),
        PagedResultDataProviderFactory::class => static fn() => new PagedResultDataProviderFactory(),
    ],
    'singletons' => [
        CreateBookUseCase::class => static fn($container) => new CreateBookUseCase(
            $container->get(BookRepositoryInterface::class),
            $container->get(TransactionInterface::class),
            $container->get(EventPublisherInterface::class)
        ),
        UpdateBookUseCase::class => static fn($container) => new UpdateBookUseCase(
            $container->get(BookRepositoryInterface::class),
            $container->get(TransactionInterface::class)
        ),
        DeleteBookUseCase::class => static fn($container) => new DeleteBookUseCase(
            $container->get(BookRepositoryInterface::class)
        ),
        CreateAuthorUseCase::class => static fn($container) => new CreateAuthorUseCase(
            $container->get(AuthorRepositoryInterface::class)
        ),
        UpdateAuthorUseCase::class => static fn($container) => new UpdateAuthorUseCase(
            $container->get(AuthorRepositoryInterface::class)
        ),
        DeleteAuthorUseCase::class => static fn($container) => new DeleteAuthorUseCase(
            $container->get(AuthorRepositoryInterface::class)
        ),
        SubscribeUseCase::class => static fn($container) => new SubscribeUseCase(
            $container->get(SubscriptionRepositoryInterface::class)
        ),
        BookQueryService::class => static fn($container) => new BookQueryService(
            $container->get(BookRepositoryInterface::class)
        ),
        AuthorQueryService::class => static fn($container) => new AuthorQueryService(
            $container->get(AuthorRepositoryInterface::class)
        ),
        SubscriptionQueryService::class => static fn($container) => new SubscriptionQueryService(
            $container->get(SubscriptionRepositoryInterface::class)
        ),
        ReportQueryService::class => static fn($container) => new ReportQueryService(
            $container->get(ReportRepositoryInterface::class)
        ),
        IdempotencyServiceInterface::class => static fn($container) => new IdempotencyService(
            $container->get(IdempotencyInterface::class)
        ),
    ],
];
