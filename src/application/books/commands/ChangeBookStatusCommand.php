<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;
use app\domain\values\BookStatus;

final readonly class ChangeBookStatusCommand implements CommandInterface
{
    public function __construct(
        public int $bookId,
        public BookStatus $targetStatus,
    ) {
    }
}
