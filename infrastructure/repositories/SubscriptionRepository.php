<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription as SubscriptionEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\infrastructure\persistence\Subscription;
use RuntimeException;
use yii\db\Connection;
use yii\db\IntegrityException;
use yii\db\Query;

final readonly class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function save(SubscriptionEntity $subscription): void
    {
        $ar = new Subscription();
        $ar->phone = $subscription->phone;
        $ar->author_id = $subscription->authorId;

        $this->persistSubscription($ar);

        $subscription->setId($ar->id);
    }

    /** @codeCoverageIgnore */
    private function persistSubscription(Subscription $ar): void
    {
        try {
            if (!$ar->save(false)) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : 'subscription.error.save_failed';
                throw new RuntimeException($message);
            }
        } catch (IntegrityException $e) {
            throw new AlreadyExistsException(previous: $e);
        }
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
