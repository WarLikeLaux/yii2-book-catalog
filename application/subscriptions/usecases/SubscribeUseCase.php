<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\exceptions\DomainException;

final class SubscribeUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public function execute(SubscribeCommand $command): void
    {
        if ($this->subscriptionRepository->exists($command->phone, $command->authorId)) {
            throw new DomainException('You are already subscribed to this author');
        }

        try {
            $this->subscriptionRepository->create($command->phone, $command->authorId);
        } catch (\RuntimeException $e) {
            throw new DomainException('Failed to create subscription');
        }
    }
}
