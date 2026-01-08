<?php

declare(strict_types=1);

namespace app\application\ports;

interface BookQueryServiceInterface extends BookFinderInterface, BookSearcherInterface
{
    /**
 * Determine whether a book with the given ISBN exists, optionally excluding a specific record.
 *
 * @param string $isbn The ISBN to check.
 * @param int|null $excludeId Optional book ID to exclude from the check.
 * @return bool `true` if a book with the given ISBN exists (excluding the provided ID when specified), `false` otherwise.
 */
public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}