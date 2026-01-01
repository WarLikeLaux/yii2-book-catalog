<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TranslatorInterface;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use Codeception\Test\Unit;
use yii\queue\Queue;

final class NotifySubscribersHandlerTest extends Unit
{
    public function testHandlePushesJobsForSubscribers(): void
    {
        $repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getSubscriberPhonesForBook')
            ->with(12, 100)
            ->willReturn(['+7900', '+7901']);

        $queryService = new SubscriptionQueryService($repository);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('translate')
            ->with('app', 'notification.book.released', ['title' => 'Test Book'])
            ->willReturn('message');

        $queue = $this->createMock(Queue::class);
        $queue->expects($this->exactly(2))
            ->method('push')
            ->with($this->isInstanceOf(NotifySingleSubscriberJob::class));

        $handler = new NotifySubscribersHandler($queryService, $translator);
        $handler->handle(12, 'Test Book', $queue);
    }
}
