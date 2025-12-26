<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;

final class NotifySubscribersJobTest extends Unit
{
    public function testGetTtr(): void
    {
        $job = new NotifySubscribersJob(['bookId' => 1, 'title' => 'Test']);
        $this->assertSame(300, $job->getTtr());
    }

    public function testCanRetryReturnsTrue(): void
    {
        $job = new NotifySubscribersJob(['bookId' => 1, 'title' => 'Test']);
        $this->assertTrue($job->canRetry(1, new \Exception()));
        $this->assertTrue($job->canRetry(2, new \Exception()));
    }

    public function testCanRetryReturnsFalse(): void
    {
        $job = new NotifySubscribersJob(['bookId' => 1, 'title' => 'Test']);
        $this->assertFalse($job->canRetry(3, new \Exception()));
        $this->assertFalse($job->canRetry(4, new \Exception()));
    }
}
