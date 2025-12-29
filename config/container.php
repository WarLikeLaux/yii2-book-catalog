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
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\QueueInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\SmsSenderInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\application\reports\queries\ReportQueryService;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\infrastructure\adapters\YiiCacheAdapter;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\adapters\YiiQueueAdapter;
use app\infrastructure\adapters\YiiTransactionAdapter;
use app\infrastructure\adapters\YiiTranslatorAdapter;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\AuthorRepositoryTracingDecorator;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\BookRepositoryTracingDecorator;
use app\infrastructure\repositories\IdempotencyRepository;
use app\infrastructure\repositories\ReportRepository;
use app\infrastructure\repositories\ReportRepositoryTracingDecorator;
use app\infrastructure\repositories\SubscriptionRepository;
use app\infrastructure\repositories\SubscriptionRepositoryTracingDecorator;
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
        // Инфраструктурные сервисы с явной конфигурацией
        SmsSenderInterface::class => static fn() => new SmsPilotSender(
            (string)env('SMS_API_KEY', 'MOCK_KEY'),
            new YiiPsrLogger('sms')
        ),
        LoggerInterface::class => static fn() => new YiiPsrLogger('application'),
        FileStorageInterface::class => static fn() => new LocalFileStorage(
            '@app/web/uploads',
            '/uploads'
        ),

        // Репозитории
        NotificationInterface::class => FlashNotificationService::class,
        TranslatorInterface::class => YiiTranslatorAdapter::class,
        IdempotencyInterface::class => IdempotencyRepository::class,
        BookRepositoryInterface::class => static function (Container $c): BookRepositoryInterface {
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
        SubscriptionRepositoryInterface::class => static function (Container $c): SubscriptionRepositoryInterface {
            $repo = $c->get(SubscriptionRepository::class);
            if ($c->has(TracerInterface::class)) {
                return new SubscriptionRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },

        // Адаптеры, требующие доступа к компонентам Yii::$app
        ReportRepositoryInterface::class => static function (Container $c): ReportRepositoryInterface {
            $repo = new ReportRepository(Yii::$app->get('db'));
            if ($c->has(TracerInterface::class)) {
                return new ReportRepositoryTracingDecorator($repo, $c->get(TracerInterface::class));
            }
            return $repo;
        },
        TransactionInterface::class => static fn() => new YiiTransactionAdapter(Yii::$app->get('db')),
        QueueInterface::class => static fn() => new YiiQueueAdapter(Yii::$app->get('queue')),
        CacheInterface::class => static fn() => new YiiCacheAdapter(Yii::$app->get('cache')),
        EventPublisherInterface::class => YiiEventPublisherAdapter::class,
    ],
    'singletons' => [
        // Все UseCases и QueryServices разрешаются автоматически через конструктор (Autowiring)
        CreateBookUseCase::class,
        UpdateBookUseCase::class,
        DeleteBookUseCase::class,
        CreateAuthorUseCase::class,
        UpdateAuthorUseCase::class,
        DeleteAuthorUseCase::class,
        SubscribeUseCase::class,
        BookQueryService::class,
        AuthorQueryService::class,
        SubscriptionQueryService::class,
        ReportQueryService::class,
        IdempotencyServiceInterface::class => IdempotencyService::class,
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
