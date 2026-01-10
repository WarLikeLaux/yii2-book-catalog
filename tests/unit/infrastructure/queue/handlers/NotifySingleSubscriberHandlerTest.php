<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class NotifySingleSubscriberHandlerTest extends Unit
{
    private const string TEST_SECRET = 'test-secret';
    private const string PHONE = '+7900';
    private const string MESSAGE = 'message';
    private const int BOOK_ID = 15;

    public function testHandleSendsSmsWhenAcquireSucceeds(): void
    {
        $idempotencyKey = $this->buildIdempotencyKey(self::BOOK_ID, self::PHONE);
        $sender = $this->expectSend(self::PHONE, self::MESSAGE);

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->expects($this->once())
            ->method('acquire')
            ->with($idempotencyKey)
            ->willReturn(true);
        $storage->expects($this->never())
            ->method('release');

        $logger = $this->createMock(LoggerInterface::class);
        $this->expectInfoContext($logger, 'SMS notification sent successfully', ['phone', 'book_id']);

        $handler = $this->createHandler($sender, $storage, $logger);
        $handler->handle(self::PHONE, self::MESSAGE, self::BOOK_ID);
    }

    public function testHandleSkipsWhenAcquireFails(): void
    {
        $idempotencyKey = $this->buildIdempotencyKey(self::BOOK_ID, self::PHONE);

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
        $this->expectInfoContext($logger, 'Skipping duplicate SMS notification', ['phone', 'book_id', 'idempotency_key']);

        $handler = $this->createHandler($sender, $storage, $logger);
        $handler->handle(self::PHONE, self::MESSAGE, self::BOOK_ID);
    }

    public function testHandleReleasesOnFailure(): void
    {
        $idempotencyKey = $this->buildIdempotencyKey(self::BOOK_ID, self::PHONE);

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
            ->method('release')
            ->with($idempotencyKey);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS notification failed', $this->anything());

        $handler = $this->createHandler($sender, $storage, $logger);

        $this->expectException(RuntimeException::class);
        $handler->handle(self::PHONE, self::MESSAGE, self::BOOK_ID);
    }

    public function testHandleLogsErrorWhenReleaseFails(): void
    {
        $idempotencyKey = $this->buildIdempotencyKey(self::BOOK_ID, self::PHONE);

        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->method('send')->willThrowException(new RuntimeException('primary fail'));

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->method('acquire')->with($idempotencyKey)->willReturn(true);
        $storage->method('release')->willThrowException(new RuntimeException('release fail'));

        $logger = $this->createMock(LoggerInterface::class);

        $matcher = $this->exactly(2);
        $logger->expects($matcher)
            ->method('error')
            ->willReturnCallback(function (string $message, array $context = []) use ($matcher): void {
                $this->assertIsArray($context);
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame('Failed to release idempotency key', $message),
                    2 => $this->assertSame('SMS notification failed', $message),
                    default => null,
                };
            });

        $handler = $this->createHandler($sender, $storage, $logger);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('primary fail');

        $handler->handle(self::PHONE, self::MESSAGE, self::BOOK_ID);
    }

    public function testHandleMasksShortPhone(): void
    {
        $shortPhone = '1234';
        $idempotencyKey = $this->buildIdempotencyKey(42, $shortPhone);

        $sender = $this->expectSend($shortPhone, 'msg');

        $storage = $this->createMock(AsyncIdempotencyStorageInterface::class);
        $storage->method('acquire')->with($idempotencyKey)->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('SMS notification sent successfully', $this->callback(
                static fn (array $ctx): bool => $ctx['phone'] === '****',
            ));

        $handler = $this->createHandler($sender, $storage, $logger);
        $handler->handle($shortPhone, 'msg', 42);
    }

    private function buildIdempotencyKey(int $bookId, string $phone): string
    {
        return sprintf('sms:%d:%s', $bookId, hash_hmac('sha256', $phone, self::TEST_SECRET));
    }

    private function createHandler(
        SmsSenderInterface $sender,
        AsyncIdempotencyStorageInterface $storage,
        LoggerInterface $logger,
    ): NotifySingleSubscriberHandler {
        return new NotifySingleSubscriberHandler($sender, $storage, $logger, self::TEST_SECRET);
    }

    private function expectSend(string $phone, string $message): SmsSenderInterface&MockObject
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->with($phone, $message);

        return $sender;
    }

    private function expectInfoContext(LoggerInterface&MockObject $logger, string $message, array $keys): void
    {
        $logger->expects($this->once())
            ->method('info')
            ->with($message, $this->callback(static function (array $context) use ($keys): bool {
                foreach ($keys as $key) {
                    if (!array_key_exists($key, $context)) {
                        return false;
                    }
                }

                return true;
            }));
    }
}
