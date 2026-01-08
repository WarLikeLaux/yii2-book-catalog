<?php

declare(strict_types=1);

use app\application\common\IdempotencyService;
use app\application\common\IdempotencyServiceInterface;
use app\application\common\RateLimitService;
use app\application\common\RateLimitServiceInterface;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookFinderInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookSearcherInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\ReportQueryServiceInterface;
use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\queries\AuthorQueryService;
use app\infrastructure\queries\BookQueryService;
use app\infrastructure\queries\decorators\BookQueryServiceTracingDecorator;
use app\infrastructure\queries\decorators\ReportQueryServiceCachingDecorator;
use app\infrastructure\queries\ReportQueryService;
use app\infrastructure\queries\SubscriptionQueryService as InfraSubscriptionQueryService;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\storage\LocalFileStorage;
use app\infrastructure\services\storage\StorageConfig;
use app\infrastructure\services\YiiPsrLogger;
use app\presentation\services\FileUrlResolver;
use yii\di\Container;

return static fn (array $params) => [
    'definitions' => [
        BookQueryServiceInterface::class => static fn(Container $c): BookQueryServiceInterface => TracingFactory::create(
            $c,
            BookQueryService::class,
            BookQueryServiceTracingDecorator::class,
        ),

        BookFinderInterface::class => BookQueryServiceInterface::class,
        BookSearcherInterface::class => BookQueryServiceInterface::class,

        AuthorQueryServiceInterface::class => AuthorQueryService::class,

        SubscriptionQueryServiceInterface::class => InfraSubscriptionQueryService::class,

        ReportQueryServiceInterface::class => static fn(Container $c): ReportQueryServiceInterface => new ReportQueryServiceCachingDecorator(
            $c->get(ReportQueryService::class),
            $c->get(CacheInterface::class),
            $params['reports']['cacheTtl'] ?? 3600,
        ),

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
            $c->get(SubscriptionQueryServiceInterface::class),
            $c->get(TranslatorInterface::class),
            new YiiPsrLogger(LogCategory::SMS),
        ),

        FileUrlResolver::class => static function () use ($params) {
            $storageParams = $params['storage'];
            return new FileUrlResolver(
                $storageParams['baseUrl'],
                $storageParams['placeholderUrl'] ?? '',
            );
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
