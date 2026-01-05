<?php

declare(strict_types=1);

namespace app\application\subscriptions\commands;

final readonly class SubscribeCommand
{
    public function __construct(
        public string $phone,
        public int $authorId,
    ) {
    }
}
