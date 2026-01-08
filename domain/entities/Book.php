<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\common\IdentifiableEntityInterface;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;

final class Book implements IdentifiableEntityInterface
{
    private const int MAX_TITLE_LENGTH = 255;

    // phpcs:disable PSR2.Classes.PropertyDeclaration,Generic.WhiteSpace.ScopeIndent,SlevomatCodingStandard.ControlStructures.BlockControlStructureSpacing
    /** @var int[] */
    public private(set) array $authorIds = [];
    public private(set) string $title {
        set {
            $trimmed = trim($value);
            if ($trimmed === '') {
        throw new ValidationException(DomainErrorCode::BookTitleEmpty);
            }
            if (mb_strlen($trimmed) > self::MAX_TITLE_LENGTH) {
        throw new ValidationException(DomainErrorCode::BookTitleTooLong);
            }
            $this->title = $trimmed;
        }
    }
    // phpcs:enable

    /**
     * Create a Book instance populated with the given state.
     *
     * @param ?int $id The book identifier, or null if not assigned.
     * @param string $title The book title.
     * @param BookYear $year The publication year value object.
     * @param Isbn $isbn The ISBN value object.
     * @param string|null $description Optional description text.
     * @param StoredFileReference|null $coverImage Optional cover image reference.
     * @param int[] $authorIds Array of author IDs; values will be cast to integers.
     * @param bool $published Whether the book is published.
     * @param int $version The entity version number.
     */
    private function __construct(
        public private(set) ?int $id,
        string $title,
        public private(set) BookYear $year,
        public private(set) Isbn $isbn,
        public private(set) ?string $description,
        public private(set) ?StoredFileReference $coverImage,
        array $authorIds,
        public private(set) bool $published,
        public private(set) int $version,
    ) {
        $this->title = $title;
        $this->authorIds = array_map(intval(...), $authorIds);
    }

    public static function create(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?StoredFileReference $coverImage,
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
            version: 1,
        );
    }

    /**
     * Recreates a Book instance from stored (persisted) state.
     *
     * @param int $id The persistent identifier of the book.
     * @param string $title The book title.
     * @param BookYear $year The book's publication year value object.
     * @param Isbn $isbn The book's ISBN value object.
     * @param string|null $description The book description, or null if none.
     * @param StoredFileReference|null $coverImage Reference to the stored cover image, or null if none.
     * @param int[] $authorIds Ordered list of author IDs associated with the book.
     * @param bool $published Whether the book is published.
     * @param int $version The persisted version number of the entity.
     * @return self A Book populated with the provided persisted values.
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
        int $version,
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
            version: $version,
        );
    }

    /**
     * Rename the book by setting a new title.
     *
     * @param string $title The new title; leading and trailing whitespace will be trimmed.
     * @throws ValidationException If the trimmed title is empty or longer than self::MAX_TITLE_LENGTH.
     */
    public function rename(string $title): void
    {
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
            throw new BusinessRuleException(DomainErrorCode::BookIsbnChangePublished);
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
            throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
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
     * @throws ValidationException|BusinessRuleException
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