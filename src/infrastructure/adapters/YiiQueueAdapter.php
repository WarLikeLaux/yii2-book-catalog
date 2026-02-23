<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\QueueInterface;
use yii\queue\db\Queue;

final readonly class YiiQueueAdapter implements QueueInterface
{
    public function __construct(
        private Queue $queue,
    ) {
    }

    public function push(object $job): void
    {
        $this->queue->push($job);
    }
}
