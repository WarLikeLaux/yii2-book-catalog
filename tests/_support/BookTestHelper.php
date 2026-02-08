<?php

declare(strict_types=1);

use app\domain\entities\Book;
use app\domain\values\BookStatus;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;

final class BookTestHelper
{
    public static function createBook(
        int $id = 42,
        string $title = 'Test Book',
        int $year = 2024,
        string $isbn = '9780132350884',
        string $description = 'Test description',
        ?string $coverImage = null,
        array $authorIds = [1],
        BookStatus $status = BookStatus::Draft,
        int $version = 1,
    ): Book {
        return Book::reconstitute(
            id: $id,
            title: $title,
            year: new BookYear($year),
            isbn: new Isbn($isbn),
            description: $description,
            coverImage: $coverImage ? new StoredFileReference($coverImage) : null,
            authorIds: $authorIds,
            status: $status,
            version: $version,
        );
    }

    public static function assignBookId(Book $book, int $id): void
    {
        $property = new ReflectionProperty(Book::class, 'id');
        $property->setValue($book, $id);
    }

    public static function setBookField(Book $book, string $field, mixed $value): void
    {
        $property = new ReflectionProperty(Book::class, $field);
        $property->setValue($book, $value);
    }
}
