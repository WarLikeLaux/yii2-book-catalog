<?php

declare(strict_types=1);

namespace app\tests\unit\application\subscriptions\usecases;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\domain\entities\Subscription;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class SubscribeUseCaseTest extends Unit
{
    private SubscriptionRepositoryInterface&MockObject $repository;
    private SubscribeUseCase $useCase;

    protected function _before(): void
    {
        $this->repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->useCase = new SubscribeUseCase($this->repository);
    }

    public function testExecuteSuccess(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->repository->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Subscription $subscription) {
                return $subscription->getPhone() === '79001112233'
                    && $subscription->getAuthorId() === 1;
            }));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsDomainExceptionWhenAlreadySubscribed(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->repository->method('exists')->willReturn(true);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('You are already subscribed to this author');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsDomainExceptionOnRepositoryError(): void
    {
        $command = new SubscribeCommand('79001112233', 1);

        $this->repository->method('exists')->willReturn(false);
        $this->repository->method('save')->willThrowException(new \Exception('DB Error'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Could not create subscription. Please try again later.');

        $this->useCase->execute($command);
    }
}