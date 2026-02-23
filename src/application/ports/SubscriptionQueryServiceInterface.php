<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionQueryServiceInterface
{
    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize = 100): iterable;
}
