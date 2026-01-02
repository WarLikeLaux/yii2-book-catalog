<?php

declare(strict_types=1);

use app\application\common\IdempotencyService;
use app\application\common\IdempotencyServiceInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\AuthServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\QueueInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\SmsSenderInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\adapters\decorators\QueueTracingDecorator;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\adapters\EventToJobMapperInterface;
use app\infrastructure\adapters\YiiAuthAdapter;
use app\infrastructure\adapters\YiiCacheAdapter;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\adapters\YiiMutexAdapter;
use app\infrastructure\adapters\YiiQueueAdapter;
use app\infrastructure\adapters\YiiTransactionAdapter;
use app\infrastructure\adapters\YiiTranslatorAdapter;
use app\infrastructure\listeners\ReportCacheInvalidationListener;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\decorators\AuthorRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\BookRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\IdempotencyRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\ReportRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\SubscriptionRepositoryTracingDecorator;
use app\infrastructure\repositories\IdempotencyRepository;
use app\infrastructure\repositories\ReportRepository;
use app\infrastructure\repositories\SubscriptionRepository;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\notifications\FlashNotificationService;
use app\infrastructure\services\observability\InspectorTracer;
use app\infrastructure\services\observability\NullTracer;
use app\infrastructure\services\sms\SmsPilotSender;
use app\infrastructure\services\storage\LocalFileStorage;
use app\infrastructure\services\YiiPsrLogger;
use Psr\Log\LoggerInterface;
use yii\di\Container;

return [
    'definitions' => [
        // Инфраструктурные сервисы
        SmsSenderInterface::class => static fn() => new SmsPilotSender(
            (string)env('SMS_API_KEY', 'MOCK_KEY'),
            new YiiPsrLogger('sms')
        ),
        LoggerInterface::class => static fn() => new YiiPsrLogger('application'),
        FileStorageInterface::class => static fn() => new LocalFileStorage(
            '@app/web/uploads',
            '/uploads'
        ),

        // Порты и адаптеры
        AuthServiceInterface::class => YiiAuthAdapter::class,
        NotificationInterface::class => FlashNotificationService::class,
        TranslatorInterface::class => YiiTranslatorAdapter::class,
        MutexInterface::class => static fn() => new YiiMutexAdapter(Yii::$app->get('mutex')),

        IdempotencyRepository::class => static fn(Container $c): IdempotencyRepository => new IdempotencyRepository(
            $c->get(LoggerInterface::class)
        ),
        IdempotencyInterface::class => static function (Container $c): IdempotencyInterface {
            $repo = $c->get(IdempotencyRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new IdempotencyRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        // Репозитории (с декораторами)
        BookRepository::class => static fn(Container $c): BookRepository => new BookRepository(
            Yii::$app->get('db')
        ),
        BookRepositoryInterface::class => static function (Container $c): BookRepositoryInterface {
            $repo = $c->get(BookRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new BookRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        BookQueryServiceInterface::class => static function (Container $c): BookQueryServiceInterface {
            $repo = $c->get(BookRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new BookRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        AuthorRepositoryInterface::class => static function (Container $c): AuthorRepositoryInterface {
            $repo = $c->get(AuthorRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new AuthorRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        SubscriptionRepository::class => static fn(): SubscriptionRepository => new SubscriptionRepository(
            Yii::$app->get('db')
        ),
        SubscriptionRepositoryInterface::class => static function (Container $c): SubscriptionRepositoryInterface {
            $repo = $c->get(SubscriptionRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new SubscriptionRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        ReportRepositoryInterface::class => static function (Container $c): ReportRepositoryInterface {
            $repo = new ReportRepository(Yii::$app->get('db'));
            if ($c->has(TracerInterface::class)) {
                return new ReportRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        // Инфраструктурные адаптеры
        TransactionInterface::class => static fn(): TransactionInterface => new YiiTransactionAdapter(
            Yii::$app->get('db')
        ),
        QueueInterface::class => static function (Container $c): QueueInterface {
            $queue = new YiiQueueAdapter(Yii::$app->get('queue'));
            if ($c->has(TracerInterface::class)) {
                return new QueueTracingDecorator($queue, $c->get(TracerInterface::class));
            }
            return $queue;
        },
        CacheInterface::class => static fn() => new YiiCacheAdapter(Yii::$app->get('cache')),
        EventToJobMapperInterface::class => EventToJobMapper::class,
        EventPublisherInterface::class => static fn(Container $c): EventPublisherInterface => new YiiEventPublisherAdapter(
            $c->get(QueueInterface::class),
            $c->get(EventToJobMapperInterface::class),
            $c->get(ReportCacheInvalidationListener::class)
        ),

        NotifySubscribersHandler::class => static fn(Container $c): NotifySubscribersHandler => new NotifySubscribersHandler(
            $c->get(SubscriptionQueryService::class),
            $c->get(TranslatorInterface::class),
            new YiiPsrLogger(LogCategory::SMS)
        ),
    ],
    'singletons' => [
        // Stateless сервисы
        IdempotencyServiceInterface::class => static fn(Container $c): IdempotencyServiceInterface => new IdempotencyService(
            $c->get(IdempotencyInterface::class),
            $c->get(MutexInterface::class)
        ),
        TracerInterface::class => static function (Container $c): TracerInterface {
            if (!env('INSPECTOR_INGESTION_KEY')) {
                return new NullTracer();
            }

            return new InspectorTracer(
                (string)env('INSPECTOR_INGESTION_KEY'),
                (string)env('INSPECTOR_URL', 'http://buggregator:8000')
            );
        },
    ],
];
