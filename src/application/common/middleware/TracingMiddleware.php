<?php

declare(strict_types=1);

namespace app\application\common\middleware;

use app\application\ports\CommandInterface;
use app\application\ports\MiddlewareInterface;
use app\application\ports\TracerInterface;
use ReflectionClass;

final readonly class TracingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TracerInterface $tracer,
    ) {
    }

    public function process(CommandInterface $command, callable $next): mixed
    {
        $shortName = (new ReflectionClass($command))->getShortName();
        $spanName = 'UseCase::' . str_replace('Command', '', $shortName);

        return $this->tracer->trace(
            $spanName,
            static fn(): mixed => $next($command),
            ['command.class' => $command::class],
        );
    }
}
