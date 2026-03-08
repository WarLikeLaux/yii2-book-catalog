<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiQueueAdapter;
use PHPUnit\Framework\TestCase;
use yii\queue\db\Queue;

final class YiiQueueAdapterTest extends TestCase
{
    public function testPushDelegatesToYiiQueue(): void
    {
        $yiiQueue = $this->createMock(Queue::class);
        $adapter = new YiiQueueAdapter($yiiQueue);
        $job = new \stdClass();

        $yiiQueue->expects($this->once())
            ->method('push')
            ->with($job);

        $adapter->push($job);
    }
}
