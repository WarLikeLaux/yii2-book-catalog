<?php

declare(strict_types=1);

namespace app\application\books\commands;

final readonly class CreateBookCommand
{
    public function __construct(
        public string $title,
        public int $year,
        public string $description,
        public string $isbn,
        public array $authorIds,
        public string|null $cover = null,
    ) {
    }
}
