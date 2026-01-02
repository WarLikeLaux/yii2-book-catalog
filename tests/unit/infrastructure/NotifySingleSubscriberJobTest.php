<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\queue\HandlerAwareQueue;
use app\infrastructure\queue\JobHandlerRegistry;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use Codeception\Test\Unit;
use Exception;
use RuntimeException;
use Yii;
use yii\queue\Queue;

final class NotifySingleSubscriberJobTest extends Unit
{
    public function testGetTtr(): void
    {
        $job = new NotifySingleSubscriberJob('+79001234567', 'Test', 1);
        $this->assertSame(30, $job->getTtr());
    }

    public function testCanRetryReturnsTrue(): void
    {
        $job = new NotifySingleSubscriberJob('+79001234567', 'Test', 1);
        $this->assertTrue($job->canRetry(1, new Exception()));
        $this->assertTrue($job->canRetry(2, new Exception()));
    }

    public function testCanRetryReturnsFalse(): void
    {
        $job = new NotifySingleSubscriberJob('+79001234567', 'Test', 1);
        $this->assertFalse($job->canRetry(3, new Exception()));
    }

    public function testExecuteDelegatesToRegistry(): void
    {
        $job = new NotifySingleSubscriberJob('+79001234567', 'Test', 1);
        $registry = $this->createMock(JobHandlerRegistry::class);

        $queue = new HandlerAwareQueue($registry, [
            'db' => Yii::$app->db,
            'tableName' => '{{%queue}}',
            'channel' => 'queue',
        ]);

        $registry->expects($this->once())
            ->method('handle')
            ->with($job, $queue);

        $job->execute($queue);
    }

    public function testExecuteThrowsWhenQueueDoesNotSupportRegistry(): void
    {
        $job = new NotifySingleSubscriberJob('+79001234567', 'Test', 1);
        $queue = $this->createMock(Queue::class);

        $this->expectException(RuntimeException::class);
        $job->execute($queue);
    }
}
