<?php

declare(strict_types=1);

namespace app\application\common\pipeline;

use app\application\common\IdempotencyServiceInterface;
use app\application\common\middleware\IdempotencyMiddleware;
use app\application\common\middleware\TracingMiddleware;
use app\application\common\middleware\TransactionMiddleware;
use app\application\ports\PipelineInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;

final readonly class PipelineFactory
{
    /**
     * Initialize the factory with dependencies used to build pipeline middleware stacks.
     *
     * @param TracerInterface $tracer Provides tracing instrumentation for the TracingMiddleware.
     * @param TransactionInterface $transaction Manages transactional boundaries for the TransactionMiddleware.
     * @param IdempotencyServiceInterface $idempotencyService Provides idempotency checks for the IdempotencyMiddleware.
     */
    public function __construct(
        private TracerInterface $tracer,
        private TransactionInterface $transaction,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    /**
     * Create a pipeline configured with tracing, idempotency, and transaction middlewares.
     *
     * @return PipelineInterface Pipeline configured with TracingMiddleware, then IdempotencyMiddleware, then TransactionMiddleware.
     */
    public function createDefault(): PipelineInterface
    {
        return (new Pipeline())
            ->pipe(new TracingMiddleware($this->tracer))
            ->pipe(new IdempotencyMiddleware($this->idempotencyService))
            ->pipe(new TransactionMiddleware($this->transaction));
    }

    /**
     * Create a pipeline configured with tracing and transaction middlewares, excluding idempotency.
     *
     * @return PipelineInterface Pipeline configured with TracingMiddleware followed by TransactionMiddleware.
     */
    public function createWithoutIdempotency(): PipelineInterface
    {
        return (new Pipeline())
            ->pipe(new TracingMiddleware($this->tracer))
            ->pipe(new TransactionMiddleware($this->transaction));
    }
}