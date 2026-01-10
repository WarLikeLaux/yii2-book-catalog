<?php

declare(strict_types=1);

use app\infrastructure\adapters\SystemClock;
use app\infrastructure\components\automapper\Yii2ActiveRecordMappingListener;
use app\infrastructure\services\YiiPsrLogger;
use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\Event\GenerateMapperEvent;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

return static fn (array $_params) => [
    ClockInterface::class => SystemClock::class,
    LoggerInterface::class => static fn() => new YiiPsrLogger('application'),

    AutoMapperInterface::class => static function (): AutoMapperInterface {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(GenerateMapperEvent::class, new Yii2ActiveRecordMappingListener());

        return AutoMapper::create(
            cacheDirectory: Yii::getAlias('@runtime/automapper'),
            eventDispatcher: $eventDispatcher,
        );
    },
];
