<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription as SubscriptionEntity;
use app\domain\exceptions\DomainErrorCode;
use app\infrastructure\persistence\Subscription;

final readonly class SubscriptionRepository extends BaseActiveRecordRepository implements SubscriptionRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function save(SubscriptionEntity $subscription): void
    {
        $ar = new Subscription();
        $ar->phone = $subscription->phone;
        $ar->author_id = $subscription->authorId;

        $this->persist($ar, DomainErrorCode::SubscriptionStaleData, DomainErrorCode::SubscriptionAlreadySubscribed);

        $this->assignId($subscription, $ar->id);
    }
}
