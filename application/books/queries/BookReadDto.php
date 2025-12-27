<?php

declare(strict_types=1);

namespace app\application\books\queries;

readonly class BookReadDto
{
    /**
     * @param int[] $authorIds
     * @param array<int, string> $authorNames Map of id => fio
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?int $year,
        public ?string $description,
        public string $isbn,
        public array $authorIds,
        public array $authorNames = [],
        public ?string $coverUrl = null
    ) {
    }
}
