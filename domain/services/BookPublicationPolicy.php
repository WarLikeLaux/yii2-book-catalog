<?php

declare(strict_types=1);

namespace app\domain\services;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;

final readonly class BookPublicationPolicy
{
    /**
     * @throws DomainException
     */
    public function ensureCanPublish(Book $book): void
    {
        if ($book->getAuthorIds() === []) {
            throw new DomainException('book.error.publish_without_authors');
        }
    }

    public function canPublish(Book $book): bool
    {
        return $book->getAuthorIds() !== [];
    }
}
