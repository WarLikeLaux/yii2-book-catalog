<?php

declare(strict_types=1);

use app\application\common\config\JaegerConfig;
use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\AuthServiceInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\QueueInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\RequestIdProviderInterface;
use app\application\ports\SmsSenderInterface;
use app\application\ports\SystemInfoProviderInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\domain\events\BookStatusChangedEvent;
use app\domain\values\BookStatus;
use app\infrastructure\adapters\AsyncIdempotencyStorage;
use app\infrastructure\adapters\decorators\IdempotencyStorageTracingDecorator;
use app\infrastructure\adapters\decorators\QueueTracingDecorator;
use app\infrastructure\adapters\decorators\RateLimitStorageTracingDecorator;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\adapters\EventSerializer;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\adapters\EventToJobMapperInterface;
use app\infrastructure\adapters\IdempotencyStorage;
use app\infrastructure\adapters\RateLimitStorage;
use app\infrastructure\adapters\SystemInfoAdapter;
use app\infrastructure\adapters\YiiAuthAdapter;
use app\infrastructure\adapters\YiiCacheAdapter;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\adapters\YiiMutexAdapter;
use app\infrastructure\adapters\YiiQueueAdapter;
use app\infrastructure\adapters\YiiTransactionAdapter;
use app\infrastructure\adapters\YiiTranslatorAdapter;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\listeners\ReportCacheInvalidationListener;
use app\infrastructure\queue\NotifySubscribersJob;
use app\infrastructure\services\notifications\FlashNotificationService;
use app\infrastructure\services\observability\OtelTracer;
use app\infrastructure\services\observability\RequestIdProvider;
use app\infrastructure\services\sms\LogSmsSender;
use app\infrastructure\services\sms\SmsPilotSender;
use app\infrastructure\services\YiiPsrLogger;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use yii\di\Container;
use yii\di\Instance;
use yii\redis\Connection as RedisConnection;

return static function (array $params): array {
    unset($params);

    return [
        'definitions' => [
            SmsSenderInterface::class => static function () {
                $apiKey = (string)env('SMS_API_KEY', 'MOCK_KEY');
                $logger = new YiiPsrLogger('sms');

                if ($apiKey === 'MOCK_KEY') {
                    return new LogSmsSender($logger);
                }

                return new SmsPilotSender($apiKey, $logger);
            },

            AuthServiceInterface::class => YiiAuthAdapter::class,
            NotificationInterface::class => FlashNotificationService::class,
            TranslatorInterface::class => YiiTranslatorAdapter::class,

            MutexInterface::class => YiiMutexAdapter::class,

            TransactionInterface::class => YiiTransactionAdapter::class,

            AsyncIdempotencyStorageInterface::class => [
                'class' => AsyncIdempotencyStorage::class,
                '__construct()' => [Instance::of(ClockInterface::class)],
            ],

            IdempotencyStorage::class => [
                'class' => IdempotencyStorage::class,
                '__construct()' => [
                    Instance::of(LoggerInterface::class),
                    Instance::of(ClockInterface::class),
                ],
            ],

            RateLimitStorage::class => [
                'class' => RateLimitStorage::class,
                '__construct()' => [
                    Instance::of(RedisConnection::class),
                    Instance::of(ClockInterface::class),
                ],
            ],

            IdempotencyInterface::class => static fn(Container $c): IdempotencyInterface => TracingFactory::create(
                $c,
                IdempotencyStorage::class,
                IdempotencyStorageTracingDecorator::class,
            ),

            RateLimitInterface::class => static fn(Container $c): RateLimitInterface => TracingFactory::create(
                $c,
                RateLimitStorage::class,
                RateLimitStorageTracingDecorator::class,
            ),

            QueueInterface::class => static fn(Container $c): QueueInterface => TracingFactory::create(
                $c,
                YiiQueueAdapter::class,
                QueueTracingDecorator::class,
            ),

            CacheInterface::class => YiiCacheAdapter::class,

            EventSerializer::class => EventSerializer::class,

            EventJobMappingRegistry::class => static fn(Container $c): EventJobMappingRegistry => new EventJobMappingRegistry(
                [
                    BookStatusChangedEvent::class => static fn(BookStatusChangedEvent $e): ?NotifySubscribersJob => $e->newStatus === BookStatus::Published
                        ? new NotifySubscribersJob($e->bookId)
                        : null,
                ],
                $c->get(EventSerializer::class),
            ),

            EventToJobMapperInterface::class => EventToJobMapper::class,

            EventPublisherInterface::class => static fn(Container $c): EventPublisherInterface => new YiiEventPublisherAdapter(
                $c->get(QueueInterface::class),
                $c->get(EventToJobMapperInterface::class),
                $c->get(ReportCacheInvalidationListener::class),
            ),

            SystemInfoProviderInterface::class => SystemInfoAdapter::class,

            RequestIdProviderInterface::class => RequestIdProvider::class,
        ],
        'singletons' => [
            TracerInterface::class => static function (Container $c): TracerInterface {
                $config = $c->get(JaegerConfig::class);

                return new OtelTracer(
                    $config->serviceName,
                    $config->endpoint,
                );
            },
        ],
    ];
};
