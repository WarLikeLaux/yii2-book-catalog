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
            ->with(48 * 3600)
            ->willReturn(5);

        $controller = new class ('idempotency', null, $storage) extends IdempotencyController {
            public function stdout($_string): int
            {
                return 0;
            }
        };

        $result = $controller->actionCleanup();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testCleanupWithCustomHours(): void
    {
        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('deleteExpired')
            ->with(1 * 3600)
            ->willReturn(3);

        $controller = new class ('idempotency', null, $storage) extends IdempotencyController {
            public function stdout($_string): int
            {
                return 0;
            }
        };

        $result = $controller->actionCleanup(1);

        $this->assertSame(ExitCode::OK, $result);
    }
}
