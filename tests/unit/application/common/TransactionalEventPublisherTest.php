<?php

declare(strict_types=1);

namespace tests\unit\application\common;

use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TransactionalEventPublisherTest extends TestCase
{
    private TransactionInterface&MockObject $transaction;
    private EventPublisherInterface&MockObject $publisher;
    private TransactionalEventPublisher $publisherService;

    protected function setUp(): void
    {
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->publisher = $this->createMock(EventPublisherInterface::class);
        $this->publisherService = new TransactionalEventPublisher(
            $this->transaction,
            $this->publisher,
        );
    }

    public function testPublishAfterCommitRegistersCallbackWithTransaction(): void
    {
        $event = new BookDeletedEvent(bookId: 42, year: 2020, wasPublished: false);
        $capturedCallback = null;

        $this->transaction->expects($this->once())
            ->method('afterCommit')
            ->willReturnCallback(static function (callable $callback) use (&$capturedCallback): void {
                $capturedCallback = $callback;
            });

        $this->publisherService->publishAfterCommit($event);

        $this->assertNotNull($capturedCallback, 'Callback should be registered with transaction');

        $this->publisher->expects($this->once())
            ->method('publishEvent')
            ->with($event);

        $capturedCallback();
    }

    public function testPublishAfterCommitCallbackPublishesEvent(): void
    {
        $event = new BookDeletedEvent(bookId: 99, year: 2024, wasPublished: true);
        $capturedCallback = null;

        $this->transaction->expects($this->once())
            ->method('afterCommit')
            ->willReturnCallback(static function (callable $callback) use (&$capturedCallback): void {
                $capturedCallback = $callback;
            });

        $this->publisher->expects($this->once())
            ->method('publishEvent')
            ->with($event);

        $this->publisherService->publishAfterCommit($event);
        $capturedCallback();
    }
}
