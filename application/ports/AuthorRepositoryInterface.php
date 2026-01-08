<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Author;

interface AuthorRepositoryInterface
{
    public function save(Author $author): void;

    /**
 * Retrieves an Author by its integer identifier.
 *
 * @param int $id The author's ID.
 * @return Author The Author entity with the given ID.
 */
public function get(int $id): Author;

    /**
 * Removes the given Author from persistent storage.
 *
 * @param Author $author The Author entity to delete.
 */
public function delete(Author $author): void;
}