<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\queue\HandlerAwareQueue;
use app\infrastructure\queue\JobHandlerRegistry;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use Exception;
use RuntimeException;
use Yii;
use yii\queue\Queue;

final class NotifySubscribersJobTest extends Unit
{
    private function createJob(): NotifySubscribersJob
    {
        return new NotifySubscribersJob(1);
    }

    public function testGetTtr(): void
    {
        $job = $this->createJob();
        $this->assertSame(300, $job->getTtr());
    }

    public function testCanRetryReturnsTrue(): void
    {
        $job = $this->createJob();
        $this->assertTrue($job->canRetry(1, new Exception()));
        $this->assertTrue($job->canRetry(2, new Exception()));
    }

    public function testCanRetryReturnsFalse(): void
    {
        $job = $this->createJob();
        $this->assertFalse($job->canRetry(3, new Exception()));
        $this->assertFalse($job->canRetry(4, new Exception()));
    }

    public function testExecuteDelegatesToRegistry(): void
    {
        $job = $this->createJob();
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
        $job = $this->createJob();
        $queue = $this->createMock(Queue::class);

        $this->expectException(RuntimeException::class);
        $job->execute($queue);
    }
}
