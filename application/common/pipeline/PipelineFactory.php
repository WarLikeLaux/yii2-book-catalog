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
    public function __construct(
        private TracerInterface $tracer,
        private TransactionInterface $transaction,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function createDefault(): PipelineInterface
    {
        return (new Pipeline())
            ->pipe(new TracingMiddleware($this->tracer))
            ->pipe(new IdempotencyMiddleware($this->idempotencyService))
            ->pipe(new TransactionMiddleware($this->transaction));
    }

    public function createWithoutIdempotency(): PipelineInterface
    {
        return (new Pipeline())
            ->pipe(new TracingMiddleware($this->tracer))
            ->pipe(new TransactionMiddleware($this->transaction));
    }
}
