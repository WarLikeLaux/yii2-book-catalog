<?php

declare(strict_types=1);

namespace app\tests\unit\application\subscriptions\usecases;

use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\PhoneNormalizerInterface;
use app\application\ports\SubscriptionExistenceCheckerInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\domain\entities\Subscription;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class SubscribeUseCaseTest extends Unit
{
    private SubscriptionRepositoryInterface&MockObject $repository;
    private AuthorExistenceCheckerInterface&MockObject $authorExistenceChecker;
    private SubscriptionExistenceCheckerInterface&MockObject $subscriptionExistenceChecker;
    private PhoneNormalizerInterface&MockObject $phoneNormalizer;
    private SubscribeUseCase $useCase;

    protected function _before(): void
    {
        $this->repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $this->subscriptionExistenceChecker = $this->createMock(SubscriptionExistenceCheckerInterface::class);
        $this->phoneNormalizer = $this->createMock(PhoneNormalizerInterface::class);
        $this->useCase = new SubscribeUseCase(
            $this->repository,
            $this->authorExistenceChecker,
            $this->subscriptionExistenceChecker,
            $this->phoneNormalizer,
        );
    }

    public function testExecuteSuccess(): void
    {
        $command = new SubscribeCommand('+7 999 111-22-33', 1);

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsById')
            ->with(1)
            ->willReturn(true);

        $this->phoneNormalizer->expects($this->once())
            ->method('normalize')
            ->with('+7 999 111-22-33')
            ->willReturn('+79991112233');

        $this->subscriptionExistenceChecker->expects($this->once())
            ->method('exists')
            ->with('+79991112233', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn(Subscription $subscription) => (string)$subscription->phone === '+79991112233'
                    && $subscription->authorId === 1))
            ->willReturn(1);

        $result = $this->useCase->execute($command);

        $this->assertTrue($result);
    }

    public function testExecuteThrowsEntityNotFoundExceptionWhenAuthorNotExists(): void
    {
        $command = new SubscribeCommand('+79001112233', 999);

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsById')
            ->with(999)
            ->willReturn(false);

        $this->phoneNormalizer->expects($this->never())->method('normalize');
        $this->subscriptionExistenceChecker->expects($this->never())->method('exists');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage(DomainErrorCode::SubscriptionInvalidAuthorId->value);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsBusinessRuleExceptionWhenAlreadySubscribed(): void
    {
        $command = new SubscribeCommand('+79001112233', 1);

        $this->authorExistenceChecker->method('existsById')->willReturn(true);
        $this->phoneNormalizer->method('normalize')->willReturn('+79001112233');
        $this->subscriptionExistenceChecker->method('exists')->willReturn(true);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage(DomainErrorCode::SubscriptionAlreadySubscribed->value);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsRuntimeExceptionOnRepositoryError(): void
    {
        $command = new SubscribeCommand('+79001112233', 1);

        $this->authorExistenceChecker->method('existsById')->willReturn(true);
        $this->phoneNormalizer->method('normalize')->willReturn('+79001112233');
        $this->subscriptionExistenceChecker->method('exists')->willReturn(false);
        $this->repository->method('save')->willThrowException(new RuntimeException('DB Error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB Error');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsApplicationExceptionOnAlreadyExists(): void
    {
        $command = new SubscribeCommand('+79001112233', 1);

        $this->authorExistenceChecker->method('existsById')->willReturn(true);
        $this->phoneNormalizer->method('normalize')->willReturn('+79001112233');
        $this->subscriptionExistenceChecker->method('exists')->willReturn(false);
        $this->repository->method('save')->willThrowException(new AlreadyExistsException());

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage('error.entity_already_exists');

        $this->useCase->execute($command);
    }

    public function testExistsCheckUsesNormalizedPhone(): void
    {
        $command = new SubscribeCommand('+7 999 111-22-33', 1);

        $this->authorExistenceChecker->method('existsById')->willReturn(true);
        $this->phoneNormalizer->method('normalize')
            ->with('+7 999 111-22-33')
            ->willReturn('+79991112233');

        $this->subscriptionExistenceChecker->expects($this->once())
            ->method('exists')
            ->with('+79991112233', 1)
            ->willReturn(true);

        $this->expectException(BusinessRuleException::class);

        $this->useCase->execute($command);
    }
}
