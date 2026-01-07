<?php

declare(strict_types=1);

use app\infrastructure\adapters\SystemClock;
use app\infrastructure\services\YiiPsrLogger;
use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

return static fn (array $_params) => [
    ClockInterface::class => SystemClock::class,
    LoggerInterface::class => static fn() => new YiiPsrLogger('application'),

    AutoMapperInterface::class => static fn(): AutoMapperInterface => AutoMapper::create(
        cacheDirectory: Yii::getAlias('@runtime/automapper'),
    ),
];
