<?php

declare(strict_types=1);

namespace app\application\ports;

interface MiddlewareInterface
{
    /**
     * @template TResult
     * @param callable(CommandInterface): TResult $next
     * @return TResult
     */
    public function process(CommandInterface $command, callable $next): mixed;
}
