<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionQueryServiceInterface
{
    public function exists(string $phone, int $authorId): bool;

    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize = 100): iterable;
}
