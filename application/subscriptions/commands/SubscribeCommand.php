<?php

declare(strict_types=1);

namespace app\application\subscriptions\commands;

use app\application\ports\CommandInterface;

final readonly class SubscribeCommand implements CommandInterface
{
    public function __construct(
        public string $phone,
        public int $authorId,
    ) {
    }
}
