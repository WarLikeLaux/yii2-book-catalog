<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use yii\queue\db\Queue;

/**
 * NOTE: Расширение Queue для решения проблемы DI в Job-ах.
 *
 * @see docs/DECISIONS.md (см. пункт "6. DI в фоновых задачах")
 */
final class HandlerAwareQueue extends Queue
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly JobHandlerRegistry $jobHandlerRegistry,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getJobHandlerRegistry(): JobHandlerRegistry
    {
        return $this->jobHandlerRegistry;
    }
}
