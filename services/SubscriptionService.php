<?php

declare(strict_types=1);

namespace app\services;

use app\models\Subscription;
use app\models\forms\SubscriptionForm;
use DomainException;

final class SubscriptionService
{
    public function subscribe(SubscriptionForm $form): Subscription
    {
        $existing = Subscription::find()
            ->where(['phone' => $form->phone, 'author_id' => $form->authorId])
            ->one();

        if ($existing) {
            return $existing;
        }

        $subscription = new Subscription();
        $subscription->phone = $form->phone;
        $subscription->author_id = $form->authorId;

        if (!$subscription->save()) {
            throw new DomainException('Failed to create subscription');
        }

        return $subscription;
    }
}

