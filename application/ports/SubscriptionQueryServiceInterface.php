<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionQueryServiceInterface
{
    /**
 * Determine whether a subscription exists for the given phone number and author.
 *
 * @param string $phone The subscriber's phone number.
 * @param int $authorId The author's identifier.
 * @return bool `true` if a subscription exists for the given phone and author, `false` otherwise.
 */
public function exists(string $phone, int $authorId): bool;

    /**
 * Yields subscriber phone numbers for a given book.
 *
 * The iterable produces phone numbers (strings) of subscribers who are subscribed to the specified book.
 *
 * @param int $bookId Identifier of the book whose subscribers will be returned.
 * @param int $batchSize Maximum number of phone numbers produced per batch; defaults to 100.
 * @return iterable<string> An iterable that yields subscriber phone numbers as strings.
 */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize = 100): iterable;
}