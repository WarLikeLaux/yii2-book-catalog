<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;

final class Book
{
    private const int MAX_TITLE_LENGTH = 255;

    /** @var int[] */
    public private(set) array $authorIds = [];

    /**
     * @param int[] $authorIds
     */
    private function __construct(
        public private(set) ?int $id,
        public private(set) string $title,
        public private(set) BookYear $year,
        public private(set) Isbn $isbn,
        public private(set) ?string $description,
        public private(set) ?StoredFileReference $coverImage,
        array $authorIds,
        public private(set) bool $published,
        public private(set) int $version
    ) {
        $this->validateTitle($title);
        $this->authorIds = array_map(intval(...), $authorIds);
    }

    public static function create(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?StoredFileReference $coverImage
    ): self {
        return new self(
            id: null,
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverImage: $coverImage,
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
        ?StoredFileReference $coverImage,
        array $authorIds,
        bool $published,
        int $version
    ): self {
        return new self(
            id: $id,
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverImage: $coverImage,
            authorIds: $authorIds,
            published: $published,
            version: $version
        );
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

    public function updateCover(?StoredFileReference $coverImage): void
    {
        $this->coverImage = $coverImage;
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

    /**
     * @throws DomainException
     */
    public function publish(BookPublicationPolicy $policy): void
    {
        $policy->ensureCanPublish($this);
        $this->published = true;
    }

    /**
     * @internal
     */
    public function incrementVersion(): void
    {
        $this->version++;
    }
}
