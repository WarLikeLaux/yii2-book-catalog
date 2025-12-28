<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionRepositoryInterface
{
    public function create(string $phone, int $authorId): void;

    public function exists(string $phone, int $authorId): bool;

    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize): iterable;
}
