<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue;

use app\infrastructure\queue\HandlerAwareQueue;
use app\infrastructure\queue\JobHandlerRegistry;
use Codeception\Test\Unit;
use Yii;

final class HandlerAwareQueueTest extends Unit
{
    public function testGetJobHandlerRegistryReturnsInjectedInstance(): void
    {
        $registry = $this->createMock(JobHandlerRegistry::class);
        $queue = new HandlerAwareQueue($registry, [
            'db' => Yii::$app->db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
        ]);

        $this->assertSame($registry, $queue->getJobHandlerRegistry());
    }
}
