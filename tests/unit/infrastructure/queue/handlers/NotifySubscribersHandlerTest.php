<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\TranslatorInterface;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use yii\queue\Queue;

final class NotifySubscribersHandlerTest extends Unit
{
    public function testHandlePushesJobsForSubscribers(): void
    {
        $bookDto = new BookReadDto(
            id: 12,
            title: 'Test Book',
            year: 2024,
            description: 'Description',
            isbn: '978-0-132-35088-4',
            authorIds: [1],
        );

        $bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $bookQueryService->expects($this->once())
            ->method('findById')
            ->with(12)
            ->willReturn($bookDto);

        $queryService = $this->createMock(SubscriptionQueryServiceInterface::class);
        $queryService->expects($this->once())
            ->method('getSubscriberPhonesForBook')
            ->with(12)
            ->willReturn(['+7900', '+7901']);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('translate')
            ->with('app', 'notification.book.released', ['title' => 'Test Book'])
            ->willReturn('message');

        $queue = $this->createMock(Queue::class);
        $queue->expects($this->exactly(2))
            ->method('push')
            ->with($this->isInstanceOf(NotifySingleSubscriberJob::class));

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new NotifySubscribersHandler($queryService, $bookQueryService, $translator, $logger);
        $handler->handle(12, $queue);
    }

    public function testHandleBookNotFoundLogsWarning(): void
    {
        $bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $bookQueryService->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $queryService = $this->createMock(SubscriptionQueryServiceInterface::class);
        $queryService->expects($this->never())->method('getSubscriberPhonesForBook');

        $translator = $this->createMock(TranslatorInterface::class);

        $queue = $this->createMock(Queue::class);
        $queue->expects($this->never())->method('push');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Book not found for notification', ['book_id' => 99]);

        $handler = new NotifySubscribersHandler($queryService, $bookQueryService, $translator, $logger);
        $handler->handle(99, $queue);
    }
}
