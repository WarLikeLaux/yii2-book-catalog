<?php

declare(strict_types=1);

namespace app\services;

use app\models\forms\SubscriptionForm;
use app\models\Subscription;
use DomainException;

final class SubscriptionService
{
    public function subscribe(SubscriptionForm $form): Subscription
    {
        $normalizedPhone = $form->phone;

        $existing = Subscription::find()
            ->where(['phone' => $normalizedPhone, 'author_id' => $form->authorId])
            ->one();

        if ($existing) {
            throw new DomainException('Вы уже подписаны на этого автора');
        }

        $subscription = new Subscription();
        $subscription->phone = $normalizedPhone;
        $subscription->author_id = $form->authorId;

        if (!$subscription->save()) {
            throw new DomainException('Failed to create subscription');
        }

        return $subscription;
    }
}
