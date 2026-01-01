<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\listeners;

use app\application\ports\CacheInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookPublishedEvent;
use app\domain\events\BookUpdatedEvent;
use app\infrastructure\listeners\ReportCacheInvalidationListener;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ReportCacheInvalidationListenerTest extends Unit
{
    private CacheInterface&MockObject $cache;

    private ReportCacheInvalidationListener $listener;

    protected function _before(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->listener = new ReportCacheInvalidationListener($this->cache);
    }

    public function testSubscribedEvents(): void
    {
        $events = $this->listener->subscribedEvents();

        $this->assertContains(BookUpdatedEvent::class, $events);
        $this->assertContains(BookDeletedEvent::class, $events);
        $this->assertContains(BookPublishedEvent::class, $events);
    }

    public function testHandleBookPublishedEventInvalidatesCache(): void
    {
        $event = new BookPublishedEvent(1, 'Test Book', 2024);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2024');

        $this->listener->handle($event);
    }

    public function testHandleBookUpdatedEventInvalidatesCacheWhenPublished(): void
    {
        $event = new BookUpdatedEvent(1, 2023, 2024, true);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function (string $key): void {
                $this->assertContains($key, [
                    'report:top_authors:2023',
                    'report:top_authors:2024',
                ]);
            });

        $this->listener->handle($event);
    }

    public function testHandleBookUpdatedEventDoesNotInvalidateCacheWhenNotPublished(): void
    {
        $event = new BookUpdatedEvent(1, 2023, 2024, false);

        $this->cache->expects($this->never())->method('delete');

        $this->listener->handle($event);
    }

    public function testHandleBookUpdatedEventInvalidatesOnlyOneYearWhenSameYear(): void
    {
        $event = new BookUpdatedEvent(1, 2024, 2024, true);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2024');

        $this->listener->handle($event);
    }

    public function testHandleBookDeletedEventInvalidatesCacheWhenWasPublished(): void
    {
        $event = new BookDeletedEvent(1, 2024, true);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('report:top_authors:2024');

        $this->listener->handle($event);
    }

    public function testHandleBookDeletedEventDoesNotInvalidateCacheWhenWasNotPublished(): void
    {
        $event = new BookDeletedEvent(1, 2024, false);

        $this->cache->expects($this->never())->method('delete');

        $this->listener->handle($event);
    }
}
