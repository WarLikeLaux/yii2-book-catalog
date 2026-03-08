<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\listeners;

use app\application\ports\CacheInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookStatusChangedEvent;
use app\domain\events\BookUpdatedEvent;
use app\domain\values\BookStatus;
use app\infrastructure\listeners\ReportCacheInvalidationListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ReportCacheInvalidationListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $listener = new ReportCacheInvalidationListener($this->createStub(CacheInterface::class));
        $events = $listener->subscribedEvents();

        $this->assertContains(BookUpdatedEvent::class, $events);
        $this->assertContains(BookDeletedEvent::class, $events);
        $this->assertContains(BookStatusChangedEvent::class, $events);
    }

    public function testHandleStatusChangedToPublishedInvalidatesCache(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookStatusChangedEvent(1, BookStatus::Draft, BookStatus::Published, 2023);

        $cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2023');

        $listener->handle($event);
    }

    public function testHandleStatusChangedFromPublishedInvalidatesCache(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookStatusChangedEvent(1, BookStatus::Published, BookStatus::Draft, 2021);

        $cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2021');

        $listener->handle($event);
    }

    public function testHandleStatusChangedUnrelatedDoesNotInvalidateCache(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookStatusChangedEvent(1, BookStatus::Draft, BookStatus::Draft, 2024);

        $cache->expects($this->never())->method('delete');

        $listener->handle($event);
    }

    public function testHandleBookUpdatedEventInvalidatesCacheWhenPublished(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookUpdatedEvent(1, 2023, 2024, BookStatus::Published);

        $cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function (string $key): void {
                $this->assertContains($key, [
                    'report:top_authors:2023',
                    'report:top_authors:2024',
                ]);
            });

        $listener->handle($event);
    }

    public function testHandleBookUpdatedEventDoesNotInvalidateCacheWhenNotPublished(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookUpdatedEvent(1, 2023, 2024, BookStatus::Draft);

        $cache->expects($this->never())->method('delete');

        $listener->handle($event);
    }

    public function testHandleBookUpdatedEventInvalidatesOnlyOneYearWhenSameYear(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookUpdatedEvent(1, 2024, 2024, BookStatus::Published);

        $cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2024');

        $listener->handle($event);
    }

    public function testHandleBookDeletedEventInvalidatesCacheWhenWasPublished(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookDeletedEvent(1, 2024, true);

        $cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2024');

        $listener->handle($event);
    }

    public function testHandleBookDeletedEventDoesNotInvalidateCacheWhenWasNotPublished(): void
    {
        [$cache, $listener] = $this->createListenerWithMock();
        $event = new BookDeletedEvent(1, 2024, false);

        $cache->expects($this->never())->method('delete');

        $listener->handle($event);
    }

    /**
     * @return array{CacheInterface&MockObject, ReportCacheInvalidationListener}
     */
    private function createListenerWithMock(): array
    {
        $cache = $this->createMock(CacheInterface::class);

        return [$cache, new ReportCacheInvalidationListener($cache)];
    }
}
