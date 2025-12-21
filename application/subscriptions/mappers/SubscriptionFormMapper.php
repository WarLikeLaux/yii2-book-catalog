<?php

declare(strict_types=1);

namespace app\application\subscriptions\mappers;

use app\application\subscriptions\commands\SubscribeCommand;
use app\models\forms\SubscriptionForm;

final class SubscriptionFormMapper
{
    public function toCommand(SubscriptionForm $form): SubscribeCommand
    {
        return new SubscribeCommand(
            phone: (string)$form->phone,
            authorId: (int)$form->authorId
        );
    }
}
