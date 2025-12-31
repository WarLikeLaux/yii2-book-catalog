<?php

declare(strict_types=1);

namespace app\application\books\queries;

use JsonSerializable;

final readonly class BookReadDto implements JsonSerializable
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
        public string|null $coverUrl = null,
        public bool $isPublished = false,
        public int $version = 1
    ) {
    }

    public function getFullTitle(): string
    {
        return $this->year !== null ? "{$this->title} ({$this->year})" : $this->title;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'year' => $this->year,
            'description' => $this->description,
            'isbn' => $this->isbn,
            'authorIds' => $this->authorIds,
            'authorNames' => $this->authorNames,
            'coverUrl' => $this->coverUrl,
            'isPublished' => $this->isPublished,
            'version' => $this->version,
        ];
    }
}
