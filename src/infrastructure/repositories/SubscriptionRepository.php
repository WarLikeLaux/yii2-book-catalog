<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\entities\Subscription as SubscriptionEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\domain\repositories\SubscriptionRepositoryInterface;
use app\infrastructure\components\hydrator\ActiveRecordHydrator;
use app\infrastructure\persistence\Subscription;

final readonly class SubscriptionRepository extends BaseActiveRecordRepository implements SubscriptionRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function __construct(
        private ActiveRecordHydrator $hydrator,
    ) {
        parent::__construct();
    }

    public function save(SubscriptionEntity $subscription): int
    {
        $model = new Subscription();

        $this->hydrator->hydrate($model, $subscription, [
            'phone' => static fn(SubscriptionEntity $e): string => (string)$e->phone,
            'author_id' => static fn(SubscriptionEntity $e): int => $e->authorId,
        ]);

        $this->persist($model, null, DomainErrorCode::SubscriptionAlreadySubscribed);

        if ($model->id === null) {
            throw new OperationFailedException(DomainErrorCode::EntityPersistFailed); // @codeCoverageIgnore
        }

        $this->assignId($subscription, $model->id);
        $this->registerIdentity($subscription, $model);

        return (int)$model->id;
    }
}
