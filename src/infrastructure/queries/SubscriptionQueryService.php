<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\infrastructure\persistence\Subscription;
use yii\db\Connection;
use yii\db\Query;

final readonly class SubscriptionQueryService implements SubscriptionQueryServiceInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    /**
     * @return iterable<string>
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
