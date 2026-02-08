<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\mappers;

use app\application\subscriptions\commands\SubscribeCommand;
use app\presentation\subscriptions\forms\SubscriptionForm;

final readonly class SubscriptionFormMapper
{
    public function toCommand(SubscriptionForm $form): SubscribeCommand
    {
        return new SubscribeCommand(
            phone: (string)$form->phone,
            authorId: (int)$form->authorId,
        );
    }
}
