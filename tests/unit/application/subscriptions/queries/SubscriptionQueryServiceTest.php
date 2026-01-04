<?php

declare(strict_types=1);

namespace tests\unit\application\subscriptions\queries;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\queries\SubscriptionQueryService;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class SubscriptionQueryServiceTest extends Unit
{
    private SubscriptionRepositoryInterface&MockObject $repository;
    private SubscriptionQueryService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->service = new SubscriptionQueryService($this->repository);
    }

    public function testGetSubscriberPhonesForBookUsesDefaultBatchSize(): void
    {
        $this->repository->expects($this->once())
            ->method('getSubscriberPhonesForBook')
            ->with(1, 100)
            ->willReturn([]);

        $this->service->getSubscriberPhonesForBook(1);
    }

    public function testGetSubscriberPhonesForBookUsesProvidedBatchSize(): void
    {
        $this->repository->expects($this->once())
            ->method('getSubscriberPhonesForBook')
            ->with(1, 50)
            ->willReturn([]);

        $this->service->getSubscriberPhonesForBook(1, 50);
    }
}
