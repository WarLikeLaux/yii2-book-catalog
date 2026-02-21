<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\application\ports\SubscriptionExistenceCheckerInterface;
use app\infrastructure\persistence\Subscription;
use yii\db\Connection;

final readonly class SubscriptionExistenceChecker implements SubscriptionExistenceCheckerInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function exists(string $phone, int $authorId): bool
    {
        return Subscription::find()
            ->where(['phone' => $phone, 'author_id' => $authorId])
            ->exists($this->db);
    }
}
