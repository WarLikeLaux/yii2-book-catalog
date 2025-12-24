<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\QueueInterface;
use yii\queue\db\Queue;

final class YiiQueueAdapter implements QueueInterface
{
    public function __construct(
        private readonly Queue $queue
    ) {
    }

    public function push(object $job): void
    {
        $this->queue->push($job);
    }
}
