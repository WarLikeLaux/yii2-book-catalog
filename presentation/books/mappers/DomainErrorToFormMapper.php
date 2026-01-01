<?php

declare(strict_types=1);

namespace app\presentation\books\mappers;

final class DomainErrorToFormMapper
{
    /** @var array<string, string> */
    private const array ERROR_TO_FIELD_MAP = [
        'isbn.error.invalid_format' => 'isbn',
        'year.error.too_old' => 'year',
        'year.error.future' => 'year',
        'book.error.title_empty' => 'title',
        'book.error.title_too_long' => 'title',
        'book.error.isbn_change_published' => 'isbn',
        'book.error.invalid_author_id' => 'authorIds',
        'book.error.publish_without_authors' => 'authorIds',
    ];

    public function getFieldForError(string $errorKey): string|null
    {
        return self::ERROR_TO_FIELD_MAP[$errorKey] ?? null;
    }
}
