<?php

declare(strict_types=1);

namespace app\application\books\queries;

final readonly class BookReadDto
{
    /**
     * @param int[] $authorIds
     * @param array<int, string> $authorNames Map of id => fio
     */
    public function __construct(
        public int $id,
        public string $title,
        public int|null $year,
        public string|null $description,
        public string $isbn,
        public array $authorIds,
        public array $authorNames = [],
        public string|null $coverUrl = null
    ) {
    }

    public function getFullTitle(): string
    {
        return $this->year !== null ? "{$this->title} ({$this->year})" : $this->title;
    }
}
