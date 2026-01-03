<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use yii\queue\db\Queue;

/**
 * Расширение Queue для решения проблемы DI в Job-ах.
 *
 * yii2-queue сериализует Job-ы, поэтому они не могут получать зависимости
 * через конструктор. Этот класс инжектит JobHandlerRegistry в очередь,
 * а Job-ы получают его через $queue->getJobHandlerRegistry().
 *
 * @see JobHandlerRegistry центр регистрации хендлеров
 * @see NotifySubscribersJob пример использования паттерна
 */
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
