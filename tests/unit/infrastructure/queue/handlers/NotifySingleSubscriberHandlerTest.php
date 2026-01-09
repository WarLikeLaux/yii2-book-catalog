<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use Codeception\Test\Unit;
use RuntimeException;

final class NotifySingleSubscriberHandlerTest extends Unit
{
    public function testHandleSendsSmsWhenAcquireSucceeds(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->with('+7900', 'message');

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->willReturn(true);
        $storage->expects($this->never())
            ->method('release');

        $handler = new NotifySingleSubscriberHandler($sender, $storage);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleSkipsWhenAcquireFails(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->never())
            ->method('send');

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->willReturn(false);
        $storage->expects($this->never())
            ->method('release');

        $handler = new NotifySingleSubscriberHandler($sender, $storage);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleReleasesOnFailure(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('fail'));

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->willReturn(true);
        $storage->expects($this->once())
            ->method('release');

        $handler = new NotifySingleSubscriberHandler($sender, $storage);

        $this->expectException(RuntimeException::class);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleLogsErrorWhenReleaseFails(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->method('send')->willThrowException(new RuntimeException('primary fail'));

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->method('acquire')->willReturn(true);
        $storage->method('release')->willThrowException(new RuntimeException('release fail'));

        $handler = new NotifySingleSubscriberHandler($sender, $storage);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('primary fail');

        $handler->handle('+7900', 'message', 15);
    }
}
