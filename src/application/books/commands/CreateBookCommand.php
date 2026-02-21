<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\common\values\AuthorIdCollection;
use app\application\ports\CommandInterface;

final readonly class CreateBookCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        public AuthorIdCollection $authorIds,
        public string|null $storedCover = null,
    ) {
    }
}
