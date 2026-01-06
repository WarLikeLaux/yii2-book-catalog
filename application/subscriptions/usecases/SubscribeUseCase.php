<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\entities\Subscription;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use Throwable;

final readonly class SubscribeUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
    ) {
    }

    public function execute(SubscribeCommand $command): void
    {
        if ($this->subscriptionRepository->exists($command->phone, $command->authorId)) {
            throw new BusinessRuleException(DomainErrorCode::SubscriptionAlreadySubscribed);
        }

        try {
            $subscription = Subscription::create($command->phone, $command->authorId);
            $this->subscriptionRepository->save($subscription);
        } catch (AlreadyExistsException $e) {
            throw $e;
        } catch (Throwable) {
            throw new OperationFailedException(DomainErrorCode::SubscriptionCreateFailed);
        }
    }
}
