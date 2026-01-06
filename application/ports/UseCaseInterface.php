<?php

declare(strict_types=1);

namespace app\application\ports;

/**
 * @template TCommand of CommandInterface
 * @template TResult
 */
interface UseCaseInterface
{
    /**
     * @param TCommand $command
     * @return TResult
     */
    public function execute(object $command): mixed;
}
