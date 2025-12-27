<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use Exception;

final class NotifySubscribersJobTest extends Unit
{
    private function createJob(): NotifySubscribersJob
    {
        return new NotifySubscribersJob(1, 'Test');
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
}
