<?php

declare(strict_types=1);

namespace app\domain\services;

use app\domain\entities\Book;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\StoredFileReference;

final readonly class BookPublicationPolicy
{
    /**
     * @throws ValidationException|BusinessRuleException
     */
    public function ensureCanPublish(Book $book): void
    {
        if ($book->authorIds === []) {
            throw new BusinessRuleException(DomainErrorCode::BookPublishWithoutAuthors);
        }

        if (!$book->coverImage instanceof StoredFileReference) {
            throw new BusinessRuleException(DomainErrorCode::BookPublishWithoutCover);
        }

        if (!$this->hasValidDescription($book->description)) {
            throw new ValidationException(DomainErrorCode::BookPublishShortDescription);
        }
    }

    public function canPublish(Book $book): bool
    {
        try {
            $this->ensureCanPublish($book);
            return true;
        } catch (ValidationException | BusinessRuleException) {
            return false;
        }
    }

    private function hasValidDescription(?string $description): bool
    {
        return $description !== null
        && mb_strlen(trim($description)) >= Book::MIN_DESCRIPTION_LENGTH;
    }
}
