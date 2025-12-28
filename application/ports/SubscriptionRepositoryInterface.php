<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;

    public function exists(string $phone, int $authorId): bool;

    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize): iterable;
}
