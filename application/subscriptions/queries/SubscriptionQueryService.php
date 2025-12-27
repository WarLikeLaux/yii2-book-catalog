<?php

declare(strict_types=1);

namespace app\application\subscriptions\queries;

use app\application\ports\SubscriptionRepositoryInterface;

final readonly class SubscriptionQueryService
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize = 100): iterable
    {
        return $this->subscriptionRepository->getSubscriberPhonesForBook($bookId, $batchSize);
    }
}
