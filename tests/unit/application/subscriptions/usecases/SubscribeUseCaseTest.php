<?php

declare(strict_types=1);

namespace app\tests\unit\application\subscriptions\usecases;

use app\application\common\exceptions\ApplicationException;
use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\domain\entities\Subscription;
use app\domain\exceptions\AlreadyExistsException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class SubscribeUseCaseTest extends Unit
{
    private SubscriptionRepositoryInterface&MockObject $repository;
    private SubscriptionQueryServiceInterface&MockObject $queryService;
    private SubscribeUseCase $useCase;

    protected function _before(): void
    {
        $this->repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->queryService = $this->createMock(SubscriptionQueryServiceInterface::class);
        $this->useCase = new SubscribeUseCase($this->repository, $this->queryService);
    }

    public function testExecuteSuccess(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->queryService->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Subscription $subscription) => $subscription->phone === '79001112233'
                    && $subscription->authorId === 1))
            ->willReturn(1);

        $result = $this->useCase->execute($command);

        $this->assertTrue($result);
    }

    public function testExecuteThrowsApplicationExceptionWhenAlreadySubscribed(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->queryService->method('exists')->willReturn(true);

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('subscription.error.already_subscribed');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsApplicationExceptionOnRepositoryError(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->queryService->method('exists')->willReturn(false);
        $this->repository->method('save')->willThrowException(new \RuntimeException('DB Error'));

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('subscription.error.create_failed');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsApplicationExceptionOnAlreadyExists(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->queryService->method('exists')->willReturn(false);
        $this->repository->method('save')->willThrowException(new AlreadyExistsException());

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('error.entity_already_exists');

        $this->useCase->execute($command);
    }
}
