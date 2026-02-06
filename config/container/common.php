<?php

declare(strict_types=1);

use app\application\common\exceptions\AlreadyExistsException;
use app\application\common\exceptions\DomainErrorMappingRegistry;
use app\application\common\exceptions\EntityNotFoundException;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\middleware\DomainExceptionTranslationMiddleware;
use app\domain\exceptions\DomainErrorCode;
use app\infrastructure\adapters\SystemClock;
use app\infrastructure\components\automapper\BookToBookReadDtoMappingListener;
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

    DomainErrorMappingRegistry::class => static function (): DomainErrorMappingRegistry {
        $registry = new DomainErrorMappingRegistry();

        $registry->register(DomainErrorCode::BookIsbnExists, AlreadyExistsException::class, field: 'isbn');
        $registry->register(DomainErrorCode::BookAuthorsNotFound, EntityNotFoundException::class, field: 'authorIds');
        $registry->register(DomainErrorCode::BookTitleEmpty, OperationFailedException::class, field: 'title');
        $registry->register(DomainErrorCode::BookNotFound, EntityNotFoundException::class);

        $registry->register(DomainErrorCode::AuthorFioExists, AlreadyExistsException::class, field: 'fio');
        $registry->register(DomainErrorCode::AuthorUpdateFailed, OperationFailedException::class, field: 'fio');
        $registry->register(DomainErrorCode::AuthorNotFound, EntityNotFoundException::class);

        $registry->register(DomainErrorCode::SubscriptionAlreadySubscribed, AlreadyExistsException::class);
        $registry->register(DomainErrorCode::SubscriptionCreateFailed, OperationFailedException::class);

        return $registry;
    },

    DomainExceptionTranslationMiddleware::class => static fn(Container $container)
        => new DomainExceptionTranslationMiddleware($container->get(DomainErrorMappingRegistry::class)),
];
