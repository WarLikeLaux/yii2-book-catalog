<?php

declare(strict_types=1);

namespace app\presentation\books\viewmodels;

use app\application\books\queries\BookReadDto;
use app\presentation\services\FileUrlResolver;
use JsonSerializable;

final readonly class BookViewModel implements JsonSerializable
{
    public int $id;

    public string $title;

    public int|null $year;

    public string|null $description;

    public string $isbn;

    /** @var array<int> */
    public array $authorIds;

    /** @var array<string> */
    public array $authorNames;

    public string|null $coverUrl;

    public bool $isPublished;

    public int $version;

    public function __construct(
        BookReadDto $dto,
        FileUrlResolver $resolver
    ) {
        $this->id = $dto->id;
        $this->title = $dto->title;
        $this->year = $dto->year;
        $this->description = $dto->description;
        $this->isbn = $dto->isbn;
        $this->authorIds = $dto->authorIds;
        $this->authorNames = $dto->authorNames;
        $this->coverUrl = $resolver->resolve($dto->coverUrl);
        $this->isPublished = $dto->isPublished;
        $this->version = $dto->version;
    }

    public function getFullTitle(): string
    {
        return $this->year !== null ? "{$this->title} ({$this->year})" : $this->title;
    }

    /**
     * @return array<string, mixed>
     */
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
