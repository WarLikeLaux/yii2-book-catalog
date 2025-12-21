<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\models\Subscription;
use yii\db\Query;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function create(string $phone, int $authorId): void
    {
        $subscription = Subscription::create($phone, $authorId);

        if (!$subscription->save()) {
            $errors = $subscription->getFirstErrors();
            $message = $errors ? array_shift($errors) : 'Failed to create subscription';
            throw new \RuntimeException($message);
        }
    }

    public function exists(string $phone, int $authorId): bool
    {
        return Subscription::find()
            ->where(['phone' => $phone, 'author_id' => $authorId])
            ->exists();
    }

    public function getSubscriberPhonesForBook(int $bookId, int $batchSize): iterable
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
