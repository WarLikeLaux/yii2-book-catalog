<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use yii\queue\db\Queue;

final class HandlerAwareQueue extends Queue
{
    public function __construct(
        private readonly JobHandlerRegistry $jobHandlerRegistry,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function getJobHandlerRegistry(): JobHandlerRegistry
    {
        return $this->jobHandlerRegistry;
    }
}
