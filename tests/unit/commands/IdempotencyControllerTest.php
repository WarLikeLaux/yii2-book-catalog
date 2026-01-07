<?php

declare(strict_types=1);

namespace tests\unit\commands;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\commands\IdempotencyController;
use Codeception\Test\Unit;
use yii\console\ExitCode;

final class IdempotencyControllerTest extends Unit
{
    public function testCleanupDeletesExpiredRecords(): void
    {
        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('deleteExpired')
            ->with(172800)
            ->willReturn(5);

        $controller = new IdempotencyController('idempotency', null, $storage);

        $result = $controller->actionCleanup();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testCleanupWithCustomHours(): void
    {
        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('deleteExpired')
            ->with(86400)
            ->willReturn(3);

        $controller = new IdempotencyController('idempotency', null, $storage);

        $result = $controller->actionCleanup(24);

        $this->assertSame(ExitCode::OK, $result);
    }
}
