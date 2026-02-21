<?php

declare(strict_types=1);

namespace app\infrastructure\adapters\decorators;

use app\application\ports\QueueInterface;
use app\application\ports\TracerInterface;
use Override;

final readonly class QueueTracingDecorator implements QueueInterface
{
    public function __construct(
        private QueueInterface $queue,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function push(object $job): void
    {
        $this->tracer->trace(
            'Queue::' . __FUNCTION__,
            fn() => $this->queue->push($job),
            ['job_class' => $job::class],
        );
    }
}
