<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\entities\Subscription;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainException;
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
            throw new DomainException('subscription.error.already_subscribed');
        }

        try {
            $subscription = Subscription::create($command->phone, $command->authorId);
            $this->subscriptionRepository->save($subscription);
        } catch (AlreadyExistsException $e) {
            throw $e;
        } catch (Throwable) {
            throw new DomainException('subscription.error.create_failed');
        }
    }
}
