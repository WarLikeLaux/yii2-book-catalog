<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;

final readonly class DeleteBookCommand implements CommandInterface
{
    public function __construct(
        public int $id,
    ) {
    }
}
