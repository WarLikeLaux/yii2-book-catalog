<?php

declare(strict_types=1);

namespace app\domain\services;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use app\domain\values\StoredFileReference;

final readonly class BookPublicationPolicy
{
    private const int MIN_DESCRIPTION_LENGTH = 50;

    /**
     * @throws DomainException
     */
    public function ensureCanPublish(Book $book): void
    {
        if ($book->authorIds === []) {
            throw new DomainException('book.error.publish_without_authors');
        }

        if (!$book->coverImage instanceof StoredFileReference) {
            throw new DomainException('book.error.publish_without_cover');
        }

        if (!$this->hasValidDescription($book->description)) {
            throw new DomainException('book.error.publish_short_description');
        }
    }

    public function canPublish(Book $book): bool
    {
        return $book->authorIds !== []
            && $book->coverImage instanceof StoredFileReference
            && $this->hasValidDescription($book->description);
    }

    private function hasValidDescription(?string $description): bool
    {
        return $description !== null
            && mb_strlen(trim($description)) >= self::MIN_DESCRIPTION_LENGTH;
    }
}
