<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription as SubscriptionEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\infrastructure\persistence\Subscription;

final readonly class SubscriptionRepository extends BaseActiveRecordRepository implements SubscriptionRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function save(SubscriptionEntity $subscription): int
    {
        $model = new Subscription();
        $model->phone = $subscription->phone;
        $model->author_id = $subscription->authorId;

        $this->persist($model, null, DomainErrorCode::SubscriptionAlreadySubscribed);

        if ($model->id === null) {
            throw new OperationFailedException(DomainErrorCode::EntityPersistFailed); // @codeCoverageIgnore
        }

        $this->assignId($subscription, $model->id);
        $this->registerIdentity($subscription, $model);

        return (int)$model->id;
    }
}
