<?php

declare(strict_types=1);

use app\application\common\IdempotencyService;
use app\application\common\IdempotencyServiceInterface;
use app\application\common\RateLimitService;
use app\application\common\RateLimitServiceInterface;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\application\reports\queries\ReportQueryService;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\queries\AuthorQueryService;
use app\infrastructure\queries\BookQueryService;
use app\infrastructure\queries\decorators\BookQueryServiceTracingDecorator;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\storage\LocalFileStorage;
use app\infrastructure\services\storage\StorageConfig;
use app\infrastructure\services\YiiPsrLogger;
use app\presentation\services\FileUrlResolver;
use yii\di\Container;
use yii\di\Instance;

return static fn (array $params) => [
    'definitions' => [
        BookQueryServiceInterface::class => static fn(Container $c): BookQueryServiceInterface => TracingFactory::create(
            $c,
            BookQueryService::class,
            BookQueryServiceTracingDecorator::class,
        ),

        AuthorQueryServiceInterface::class => AuthorQueryService::class,

        ReportQueryService::class => [
            'class' => ReportQueryService::class,
            '__construct()' => [
                Instance::of(ReportRepositoryInterface::class),
                Instance::of(CacheInterface::class),
                $params['reports']['cacheTtl'] ?? 3600,
            ],
        ],

        FileStorageInterface::class => static function () use ($params) {
            $storageParams = $params['storage'];
            $config = new StorageConfig(
                $storageParams['basePath'],
                $storageParams['baseUrl'],
                $storageParams['tempBasePath'],
                $storageParams['tempBaseUrl'],
            );
            return new LocalFileStorage($config);
        },

        NotifySubscribersHandler::class => static fn(Container $c): NotifySubscribersHandler => new NotifySubscribersHandler(
            $c->get(SubscriptionQueryService::class),
            $c->get(TranslatorInterface::class),
            new YiiPsrLogger(LogCategory::SMS),
        ),

        FileUrlResolver::class => static function () use ($params) {
            $storageParams = $params['storage'];
            return new FileUrlResolver($storageParams['baseUrl']);
        },
    ],
    'singletons' => [
        IdempotencyServiceInterface::class => static fn(Container $c): IdempotencyServiceInterface => new IdempotencyService(
            $c->get(IdempotencyInterface::class),
            $c->get(MutexInterface::class),
        ),
        RateLimitServiceInterface::class => static fn(Container $c): RateLimitServiceInterface => new RateLimitService(
            $c->get(RateLimitInterface::class),
        ),
        TransactionalEventPublisher::class => static fn(Container $c): TransactionalEventPublisher => new TransactionalEventPublisher(
            $c->get(TransactionInterface::class),
            $c->get(EventPublisherInterface::class),
        ),
    ],
];
