<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use RuntimeException;

final class Book
{
    /** @var int[] */
    private array $authorIds = [];

    /**
     * @param int[] $authorIds
     */
    public function __construct(
        private ?int $id,
        private string $title,
        private BookYear $year,
        private Isbn $isbn,
        private ?string $description,
        private ?string $coverUrl,
        array $authorIds = [],
        private bool $published = false
    ) {
        $this->authorIds = array_map(intval(...), $authorIds);
    }

    public static function create(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): self {
        return new self(
            id: null,
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl
        );
    }

    /**
     * @throws DomainException
     */
    public function update(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): void {
        if ($this->published && !$this->isbn->equals($isbn)) {
            throw new DomainException('book.error.isbn_change_published');
        }

        $this->title = $title;
        $this->year = $year;
        $this->isbn = $isbn;
        $this->description = $description;
        if ($coverUrl === null) {
            return;
        }

        $this->coverUrl = $coverUrl;
    }

    /**
     * @param int[] $authorIds
     */
    public function syncAuthors(array $authorIds): void
    {
        $this->authorIds = array_map(intval(...), $authorIds);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @internal Только для использования репозиторием
     */
    public function setId(int $id): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new RuntimeException('Cannot overwrite ID');
        }
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getYear(): BookYear
    {
        return $this->year;
    }

    public function getIsbn(): Isbn
    {
        return $this->isbn;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    /**
     * @return int[]
     */
    public function getAuthorIds(): array
    {
        return $this->authorIds;
    }

    /**
     * @throws DomainException
     */
    public function publish(): void
    {
        if ($this->authorIds === []) {
            throw new DomainException('book.error.publish_without_authors');
        }
        $this->published = true;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
