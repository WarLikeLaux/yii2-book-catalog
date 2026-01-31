<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\dto;

use app\application\authors\queries\AuthorReadDto;
use app\presentation\subscriptions\forms\SubscriptionForm;

final readonly class SubscriptionViewModel
{
    public function __construct(
        public SubscriptionForm $form,
        public AuthorReadDto $author,
    ) {
    }
}
