<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription as SubscriptionEntity;
use app\infrastructure\persistence\Subscription;
use yii\db\Query;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function save(SubscriptionEntity $subscription): void
    {
        $ar = Subscription::create($subscription->getPhone(), $subscription->getAuthorId());

        if (!$ar->save()) {
            $errors = $ar->getFirstErrors();
            $message = $errors !== [] ? array_shift($errors) : 'Failed to create subscription'; // @codeCoverageIgnore
            throw new \RuntimeException($message);
        }

        $subscription->setId($ar->id);
    }

    public function exists(string $phone, int $authorId): bool
    {
        return Subscription::find()
            ->where(['phone' => $phone, 'author_id' => $authorId])
            ->exists();
    }

    /**
     * @return iterable<string>
     */
    public function getSubscriberPhonesForBook(int $bookId, int $batchSize): iterable
    {
        $query = (new Query())
            ->from(['s' => Subscription::tableName()])
            ->select(['s.phone'])
            ->distinct()
            ->innerJoin('book_authors ba', 'ba.author_id = s.author_id')
            ->andWhere(['ba.book_id' => $bookId]);

        /** @var iterable<array<mixed>> $batches */
        $batches = $query->batch($batchSize);
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
