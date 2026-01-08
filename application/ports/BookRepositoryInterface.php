<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Book;

interface BookRepositoryInterface
{
    public function save(Book $book): void;

    public function get(int $id): Book;

    /**
 * Retrieves a Book matching the given identifier and version.
 *
 * @param int $id The book's numeric identifier.
 * @param int $expectedVersion The expected version number to match.
 * @return Book The Book with the specified id and version.
 */
public function getByIdAndVersion(int $id, int $expectedVersion): Book;

    /**
 * Removes the given Book from the repository or persistence store.
 *
 * @param Book $book The Book entity to delete.
 */
public function delete(Book $book): void;
}