<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

final readonly class BookViewModel
{
    /**
     * @param array<int, string> $authorNames
     */
    public function __construct(
        public int $id,
        public string $title,
        public int|null $year,
        public string|null $description,
        public string $isbn,
        public array $authorNames,
        public string|null $coverUrl,
        public bool $isPublished,
    ) {
    }
}
