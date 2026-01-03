<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use RuntimeException;

final class Book
{
    private const int MAX_TITLE_LENGTH = 255;

    /** @var int[] */
    private array $authorIds = [];

    private ?int $id = null;

    /**
     * @param int[] $authorIds
     */
    private function __construct(
        private string $title,
        private BookYear $year,
        private Isbn $isbn,
        private ?string $description,
        private ?string $coverUrl,
        array $authorIds,
        private bool $published,
        private int $version
    ) {
        $this->validateTitle($title);
        $this->authorIds = array_map(intval(...), $authorIds);
    }

    private function validateTitle(string $title): void
    {
        $trimmed = trim($title);

        if ($trimmed === '') {
            throw new DomainException('book.error.title_empty');
        }

        if (mb_strlen($trimmed) > self::MAX_TITLE_LENGTH) {
            throw new DomainException('book.error.title_too_long');
        }
    }

    public static function create(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): self {
        return new self(
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl,
            authorIds: [],
            published: false,
            version: 1
        );
    }

    /**
     * @param int[] $authorIds
     */
    public static function reconstitute(
        int $id,
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl,
        array $authorIds,
        bool $published,
        int $version
    ): self {
        $book = new self(
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl,
            authorIds: $authorIds,
            published: $published,
            version: $version
        );

        return $book->withId($id);
    }

    public function rename(string $title): void
    {
        $this->validateTitle($title);
        $this->title = $title;
    }

    public function changeYear(BookYear $year): void
    {
        $this->year = $year;
    }

    public function correctIsbn(Isbn $isbn): void
    {
        if ($this->isbn->equals($isbn)) {
            return;
        }

        if ($this->published) {
            throw new DomainException('book.error.isbn_change_published');
        }

        $this->isbn = $isbn;
    }

    public function updateDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function updateCover(?string $coverUrl): void
    {
        $this->coverUrl = $coverUrl;
    }

    public function addAuthor(int $authorId): void
    {
        if ($authorId <= 0) {
            throw new DomainException('book.error.invalid_author_id');
        }

        if (in_array($authorId, $this->authorIds, true)) {
            return;
        }

        $this->authorIds[] = $authorId;
    }

    public function removeAuthor(int $authorId): void
    {
        $key = array_search($authorId, $this->authorIds, true);
        if ($key === false) {
            return;
        }

        unset($this->authorIds[$key]);
        $this->authorIds = array_values($this->authorIds);
    }

    public function hasAuthor(int $authorId): bool
    {
        return in_array($authorId, $this->authorIds, true);
    }

    /**
     * @param int[] $authorIds
     */
    public function replaceAuthors(array $authorIds): void
    {
        $this->authorIds = [];
        foreach ($authorIds as $authorId) {
            $this->addAuthor($authorId);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    private function setId(int $id): void
    {
        if ($this->id !== null && $this->id !== $id) {
            throw new RuntimeException('Cannot overwrite ID');
        }
        $this->id = $id;
    }

    private function withId(int $id): self
    {
        $this->setId($id);
        return $this;
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

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @internal
     */
    public function incrementVersion(): void
    {
        $this->version++;
    }
}
