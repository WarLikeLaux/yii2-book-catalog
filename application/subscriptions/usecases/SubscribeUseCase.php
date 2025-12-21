<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\exceptions\DomainException;
use app\models\Subscription;
use Yii;

final class SubscribeUseCase
{
    public function execute(SubscribeCommand $command): void
    {
        $existing = Subscription::find()
            ->where(['phone' => $command->phone, 'author_id' => $command->authorId])
            ->one();

        if ($existing) {
            throw new DomainException(Yii::t('app', 'You are already subscribed to this author'));
        }

        $subscription = Subscription::create($command->phone, $command->authorId);

        if (!$subscription->save()) {
            throw new DomainException(Yii::t('app', 'Failed to create subscription'));
        }
    }
}
