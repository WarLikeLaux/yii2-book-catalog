<?php

declare(strict_types=1);

namespace app\application\ports;

interface PipelineInterface
{
    /**
     * @template TResponse
     * @template TCommand of CommandInterface
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @return TResponse
     */
    public function execute(CommandInterface $command, UseCaseInterface $useCase): mixed;
}
