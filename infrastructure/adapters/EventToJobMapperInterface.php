<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\QueueableEvent;
use yii\queue\JobInterface;

interface EventToJobMapperInterface
{
    public function map(QueueableEvent $event): JobInterface;
}
