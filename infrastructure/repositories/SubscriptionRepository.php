<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription as SubscriptionEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\infrastructure\persistence\Subscription;
use RuntimeException;
use yii\db\IntegrityException;

final readonly class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;
    use IdentityAssignmentTrait;

    public function save(SubscriptionEntity $subscription): void
    {
        $ar = new Subscription();
        $ar->phone = $subscription->phone;
        $ar->author_id = $subscription->authorId;

        $this->persistSubscription($ar);

        $this->assignId($subscription, $ar->id);
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
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException(previous: $e);
            }

            throw $e;
        }
    }
}
