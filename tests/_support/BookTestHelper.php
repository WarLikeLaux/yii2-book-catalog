<?php

declare(strict_types=1);

use app\domain\entities\Book;
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
        bool $published = false,
        int $version = 1
    ): Book {
        return Book::reconstitute(
            id: $id,
            title: $title,
            year: new BookYear($year, new DateTimeImmutable()),
            isbn: new Isbn($isbn),
            description: $description,
            coverImage: $coverImage ? new StoredFileReference($coverImage) : null,
            authorIds: $authorIds,
            published: $published,
            version: $version
        );
    }

    public static function assignBookId(Book $book, int $id): void
    {
        $method = new ReflectionMethod(Book::class, 'setId');
        $method->invoke($book, $id);
    }

    public static function setBookField(Book $book, string $field, mixed $value): void
    {
        $property = new ReflectionProperty(Book::class, $field);
        $property->setValue($book, $value);
    }
}
