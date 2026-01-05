<?php

declare(strict_types=1);

use app\infrastructure\adapters\SystemClock;
use app\infrastructure\services\YiiPsrLogger;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

return static fn (array $_params) => [
    ClockInterface::class => SystemClock::class,
    LoggerInterface::class => static fn() => new YiiPsrLogger('application'),
];
