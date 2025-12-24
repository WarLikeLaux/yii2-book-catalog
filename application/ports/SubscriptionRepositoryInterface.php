<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionRepositoryInterface
{
    public function create(string $phone, int $authorId): void;

    public function exists(string $phone, int $authorId): bool;

    public function getSubscriberPhonesForBook(int $bookId, int $batchSize): iterable;
}
