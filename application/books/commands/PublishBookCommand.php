<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;

final readonly class PublishBookCommand implements CommandInterface
{
    public function __construct(
        public int $bookId,
    ) {
    }
}
