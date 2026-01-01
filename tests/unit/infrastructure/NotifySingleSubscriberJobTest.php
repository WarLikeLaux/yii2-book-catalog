<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\queue\NotifySingleSubscriberJob;
use Codeception\Test\Unit;
use Exception;

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
}
