<?php

declare(strict_types=1);

use app\application\common\config\BuggregatorConfig;
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
use app\domain\events\BookPublishedEvent;
use app\infrastructure\adapters\decorators\QueueTracingDecorator;
use app\infrastructure\adapters\EventJobMappingRegistry;
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
use app\infrastructure\queue\NotifySubscribersJob;
use app\infrastructure\services\notifications\FlashNotificationService;
use app\infrastructure\services\observability\InspectorTracer;
use app\infrastructure\services\observability\NullTracer;
use app\infrastructure\services\sms\LogSmsSender;
use app\infrastructure\services\sms\SmsPilotSender;
use app\infrastructure\services\YiiPsrLogger;
use yii\di\Container;

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

            QueueInterface::class => static fn(Container $c): QueueInterface => TracingFactory::create(
                $c,
                YiiQueueAdapter::class,
                QueueTracingDecorator::class,
            ),

            CacheInterface::class => YiiCacheAdapter::class,

            EventJobMappingRegistry::class => static fn(): EventJobMappingRegistry => new EventJobMappingRegistry([
                BookPublishedEvent::class => NotifySubscribersJob::class,
            ]),

            EventToJobMapperInterface::class => EventToJobMapper::class,

            EventPublisherInterface::class => static fn(Container $c): EventPublisherInterface => new YiiEventPublisherAdapter(
                $c->get(QueueInterface::class),
                $c->get(EventToJobMapperInterface::class),
                $c->get(ReportCacheInvalidationListener::class),
            ),

            SystemInfoProviderInterface::class => SystemInfoAdapter::class,
        ],
        'singletons' => [
            TracerInterface::class => static function (Container $c): TracerInterface {
                $config = $c->get(BuggregatorConfig::class);
                $ingestionKey = $config->inspector->ingestionKey;

                if ($ingestionKey === '' || $ingestionKey === 'buggregator') {
                    return new NullTracer();
                }

                return new InspectorTracer(
                    $ingestionKey,
                    $config->inspector->url,
                );
            },
        ],
    ];
};
