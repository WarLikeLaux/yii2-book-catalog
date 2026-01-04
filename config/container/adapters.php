<?php

declare(strict_types=1);

use app\application\ports\AuthServiceInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\MutexInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\QueueInterface;
use app\application\ports\SmsSenderInterface;
use app\application\ports\SystemInfoProviderInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use app\application\ports\TranslatorInterface;
use app\infrastructure\adapters\decorators\QueueTracingDecorator;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\adapters\EventToJobMapperInterface;
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
use app\infrastructure\services\notifications\FlashNotificationService;
use app\infrastructure\services\observability\InspectorTracer;
use app\infrastructure\services\observability\NullTracer;
use app\infrastructure\services\sms\LogSmsSender;
use app\infrastructure\services\sms\SmsPilotSender;
use app\infrastructure\services\YiiPsrLogger;
use yii\di\Container;

return static fn (array $_params) => [
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

            MutexInterface::class => static fn() => new YiiMutexAdapter(Yii::$app->get('mutex')),

            TransactionInterface::class => static fn() => new YiiTransactionAdapter(Yii::$app->get('db')),

            YiiQueueAdapter::class => static fn() => new YiiQueueAdapter(Yii::$app->get('queue')),

            QueueInterface::class => static fn(Container $c): QueueInterface => TracingFactory::create(
                $c,
                YiiQueueAdapter::class,
                QueueTracingDecorator::class,
            ),

            CacheInterface::class => static fn() => new YiiCacheAdapter(Yii::$app->get('cache')),

            EventToJobMapperInterface::class => EventToJobMapper::class,

            EventPublisherInterface::class => static fn(Container $c): EventPublisherInterface => new YiiEventPublisherAdapter(
                $c->get(QueueInterface::class),
                $c->get(EventToJobMapperInterface::class),
                $c->get(ReportCacheInvalidationListener::class),
            ),

            SystemInfoProviderInterface::class => static fn() => new SystemInfoAdapter(Yii::$app->get('db')),
        ],
        'singletons' => [
            TracerInterface::class => static function (Container $_c): TracerInterface {
                if (!env('INSPECTOR_INGESTION_KEY')) {
                    return new NullTracer();
                }

                return new InspectorTracer(
                    (string)env('INSPECTOR_INGESTION_KEY'),
                    (string)env('INSPECTOR_URL', 'http://buggregator:8000'),
                );
            },
        ],
    ];
