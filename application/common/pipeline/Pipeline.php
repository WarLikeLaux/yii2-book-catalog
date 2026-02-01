<?php

declare(strict_types=1);

namespace app\application\common\pipeline;

use app\application\common\exceptions\ApplicationException;
use app\application\ports\CommandInterface;
use app\application\ports\MiddlewareInterface;
use app\application\ports\PipelineInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainException;

final class Pipeline implements PipelineInterface
{
    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;

        return $clone;
    }

    /**
     * @template TResponse
     * @template TCommand of CommandInterface
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @return TResponse
     */
    public function execute(CommandInterface $command, UseCaseInterface $useCase): mixed
    {
        /** @phpstan-ignore argument.type */
        $handler = static fn(CommandInterface $cmd): mixed => $useCase->execute($cmd);

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $handler;
            $handler = static fn(CommandInterface $cmd): mixed => $middleware->process($cmd, $next);
        }

        try {
            /** @var TResponse */
            return $handler($command);
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
