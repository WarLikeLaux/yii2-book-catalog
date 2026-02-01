<?php

declare(strict_types=1);

use app\application\common\config\BuggregatorConfig;
use app\application\common\config\ConfigFactory;
use app\application\common\config\IdempotencyConfig;
use app\application\common\config\RateLimitConfig;
use app\application\common\config\ReportsConfig;
use app\application\common\config\StorageConfig as AppStorageConfig;
use app\application\common\IdempotencyService;
use app\application\common\IdempotencyServiceInterface;
use app\application\common\RateLimitService;
use app\application\common\RateLimitServiceInterface;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookFinderInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookSearcherInterface;
use app\application\ports\CacheInterface;
use app\application\ports\ContentStorageInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MimeTypeDetectorInterface;
use app\application\ports\MutexInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\ReportQueryServiceInterface;
use app\application\ports\SmsSenderInterface;
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
use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\NativeMimeTypeDetector;
use app\infrastructure\services\storage\ContentAddressableStorage;
use app\infrastructure\services\storage\StorageConfig as InfraStorageConfig;
use app\infrastructure\services\YiiPsrLogger;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\services\FileUrlResolver;
use yii\di\Container;

return static function (array $params): array {
    $configFactory = new ConfigFactory($params);

    return [
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

            ReportQueryServiceInterface::class => static function (Container $c): ReportQueryServiceInterface {
                $reportsConfig = $c->get(ReportsConfig::class);

                return new ReportQueryServiceCachingDecorator(
                    $c->get(ReportQueryService::class),
                    $c->get(CacheInterface::class),
                    $reportsConfig->cacheTtl,
                );
            },

            ContentStorageInterface::class => static function (Container $c): ContentStorageInterface {
                $storageConfig = $c->get(AppStorageConfig::class);

                $config = new InfraStorageConfig(
                    Yii::getAlias($storageConfig->basePath),
                    $storageConfig->baseUrl,
                );

                return new ContentAddressableStorage($config);
            },

            MimeTypeDetectorInterface::class => NativeMimeTypeDetector::class,

            UploadedFileAdapter::class => UploadedFileAdapter::class,

            NotifySubscribersHandler::class => static fn(Container $c): NotifySubscribersHandler => new NotifySubscribersHandler(
                $c->get(SubscriptionQueryServiceInterface::class),
                $c->get(TranslatorInterface::class),
                new YiiPsrLogger(LogCategory::SMS),
            ),
            ConfigFactory::class => static fn(): ConfigFactory => $configFactory,
            IdempotencyConfig::class => static fn(): IdempotencyConfig => $configFactory->idempotency(),
            RateLimitConfig::class => static fn(): RateLimitConfig => $configFactory->rateLimit(),
            ReportsConfig::class => static fn(): ReportsConfig => $configFactory->reports(),
            AppStorageConfig::class => static fn(): AppStorageConfig => $configFactory->storage(),
            BuggregatorConfig::class => static fn(): BuggregatorConfig => $configFactory->buggregator(),

            NotifySingleSubscriberHandler::class => static function (Container $c): NotifySingleSubscriberHandler {
                $idempotencyConfig = $c->get(IdempotencyConfig::class);
                return new NotifySingleSubscriberHandler(
                    $c->get(SmsSenderInterface::class),
                    $c->get(AsyncIdempotencyStorageInterface::class),
                    new YiiPsrLogger(LogCategory::SMS),
                    $idempotencyConfig->smsPhoneHashKey,
                );
            },

            FileUrlResolver::class => static function (Container $c): FileUrlResolver {
                $storageConfig = $c->get(AppStorageConfig::class);
                return new FileUrlResolver(
                    $storageConfig->baseUrl,
                    $storageConfig->placeholderUrl,
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
};
