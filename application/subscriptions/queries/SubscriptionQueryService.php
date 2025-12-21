<?php

declare(strict_types=1);

namespace app\application\subscriptions\queries;

use app\models\Subscription;
use yii\db\Query;

final class SubscriptionQueryService
{
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

        foreach ($query->batch($batchSize) as $batch) {
            foreach ($batch as $row) {
                yield $row['phone'];
            }
        }
    }
}
