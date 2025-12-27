<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\exceptions\DomainException;

final readonly class SubscribeUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public function execute(SubscribeCommand $command): void
    {
        if ($this->subscriptionRepository->exists($command->phone, $command->authorId)) {
            throw new DomainException('You are already subscribed to this author');
        }

        try {
            $this->subscriptionRepository->create($command->phone, $command->authorId);
        } catch (\RuntimeException) {
            throw new DomainException('Failed to create subscription');
        }
    }
}
