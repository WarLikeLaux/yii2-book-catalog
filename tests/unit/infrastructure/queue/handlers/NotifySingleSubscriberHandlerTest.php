<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class NotifySingleSubscriberHandlerTest extends Unit
{
    public function testHandleSendsSmsWhenAcquireSucceeds(): void
    {
        $secret = 'test-secret';
        $idempotencyKey = sprintf('sms:%d:%s', 15, hash_hmac('sha256', '+7900', $secret));

        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->with('+7900', 'message');

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->with($idempotencyKey)
            ->willReturn(true);
        $storage->expects($this->never())
            ->method('release');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('SMS notification sent successfully', $this->anything());

        $handler = new NotifySingleSubscriberHandler($sender, $storage, $logger, $secret);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleSkipsWhenAcquireFails(): void
    {
        $secret = 'test-secret';
        $idempotencyKey = sprintf('sms:%d:%s', 15, hash_hmac('sha256', '+7900', $secret));

        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->never())
            ->method('send');

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->with($idempotencyKey)
            ->willReturn(false);
        $storage->expects($this->never())
            ->method('release');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Skipping duplicate SMS notification', $this->anything());

        $handler = new NotifySingleSubscriberHandler($sender, $storage, $logger, $secret);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleReleasesOnFailure(): void
    {
        $secret = 'test-secret';
        $idempotencyKey = sprintf('sms:%d:%s', 15, hash_hmac('sha256', '+7900', $secret));

        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('fail'));

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->with($idempotencyKey)
            ->willReturn(true);
        $storage->expects($this->once())
            ->method('release');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS notification failed', $this->anything());

        $handler = new NotifySingleSubscriberHandler($sender, $storage, $logger, $secret);

        $this->expectException(RuntimeException::class);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleLogsErrorWhenReleaseFails(): void
    {
        $secret = 'test-secret';
        $idempotencyKey = sprintf('sms:%d:%s', 15, hash_hmac('sha256', '+7900', $secret));

        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->method('send')->willThrowException(new RuntimeException('primary fail'));

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->method('acquire')->with($idempotencyKey)->willReturn(true);
        $storage->method('release')->willThrowException(new RuntimeException('release fail'));

        $logger = $this->createMock(LoggerInterface::class);

        $matcher = $this->exactly(2);
        $logger->expects($matcher)
            ->method('error')
            ->willReturnCallback(function (string $message) use ($matcher): void {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame('Failed to release idempotency key', $message),
                    2 => $this->assertSame('SMS notification failed', $message),
                    default => null,
                };
            });

        $handler = new NotifySingleSubscriberHandler($sender, $storage, $logger, $secret);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('primary fail');

        $handler->handle('+7900', 'message', 15);
    }
}
