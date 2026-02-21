<?php

declare(strict_types=1);

use app\application\common\exceptions\DomainErrorMappingRegistry;
use app\application\common\middleware\DomainExceptionTranslationMiddleware;
use app\infrastructure\adapters\SystemClock;
use app\infrastructure\components\automapper\BookToBookReadDtoMappingListener;
use app\infrastructure\components\automapper\FormToBookCommandMappingListener;
use app\infrastructure\components\automapper\ValueObjectStringPropertyTransformer;
use app\infrastructure\components\automapper\Yii2ActiveRecordMappingListener;
use app\infrastructure\services\YiiPsrLogger;
use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\Configuration;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Loader\FileReloadStrategy;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use yii\di\Container;

return static fn (array $_params) => [
    ClockInterface::class => SystemClock::class,
    LoggerInterface::class => static fn() => new YiiPsrLogger('application'),

    AutoMapperInterface::class => static function (): AutoMapperInterface {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(GenerateMapperEvent::class, new Yii2ActiveRecordMappingListener());
        $eventDispatcher->addListener(GenerateMapperEvent::class, new BookToBookReadDtoMappingListener());
        $eventDispatcher->addListener(GenerateMapperEvent::class, new FormToBookCommandMappingListener());

        $isDev = defined('YII_ENV_DEV') && YII_ENV_DEV;
        $isTest = defined('YII_ENV') && YII_ENV === 'test';
        $configuration = new Configuration(
            reloadStrategy: $isDev || $isTest ? FileReloadStrategy::ALWAYS : FileReloadStrategy::ON_CHANGE,
        );

        return AutoMapper::create(
            configuration: $configuration,
            cacheDirectory: Yii::getAlias('@runtime/automapper'),
            propertyTransformers: [
                new ValueObjectStringPropertyTransformer(),
            ],
            eventDispatcher: $eventDispatcher,
        );
    },

    DomainErrorMappingRegistry::class => static fn() => DomainErrorMappingRegistry::fromEnum(),

    DomainExceptionTranslationMiddleware::class => static fn(Container $container)
        => new DomainExceptionTranslationMiddleware($container->get(DomainErrorMappingRegistry::class)),
];
