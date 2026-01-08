<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\infrastructure\persistence\Subscription;
use yii\db\Connection;
use yii\db\Query;

final readonly class SubscriptionQueryService implements SubscriptionQueryServiceInterface
{
    /**
     * Initializes the query service with a database connection.
     *
     * Stores the provided Connection instance for use by the service's query methods.
     */
    public function __construct(
        private Connection $db,
    ) {
    }

    /**
     * Check whether a subscription exists for the given phone number and author.
     *
     * @param string $phone The subscriber's phone number to look up.
     * @param int $authorId The author's ID associated with the subscription.
     * @return bool `true` if a matching subscription exists, `false` otherwise.
     */
    public function exists(string $phone, int $authorId): bool
    {
        return Subscription::find()
            ->where(['phone' => $phone, 'author_id' => $authorId])
            ->exists($this->db);
    }

    /**
         * Yield distinct subscriber phone numbers for all authors of the given book.
         *
         * Phones are produced one at a time; query results are fetched in batches to limit memory usage.
         *
         * @param int $bookId The book identifier to find associated authors' subscribers for.
         * @param int $batchSize Maximum number of rows fetched from the database per batch.
         * @return iterable<string> An iterable that yields subscriber phone numbers as strings.
         */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize = 100): iterable
    {
        $query = (new Query())
            ->from(['s' => Subscription::tableName()])
            ->select(['s.phone'])
            ->distinct()
            ->innerJoin('book_authors ba', 'ba.author_id = s.author_id')
            ->andWhere(['ba.book_id' => $bookId]);

        /** @var iterable<array<mixed>> $batches */
        $batches = $query->batch($batchSize, $this->db);

        foreach ($batches as $batch) {
            /** @var array<string, mixed> $row */
            foreach ($batch as $row) {
                /** @var string $phone */
                $phone = $row['phone'];

                yield $phone;
            }
        }
    }
}