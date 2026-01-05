<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Book;

interface BookRepositoryInterface
{
    public function save(Book $book): void;

    public function get(int $id): Book;

    public function getByIdAndVersion(int $id, int $expectedVersion): Book;

    public function delete(Book $book): void;

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
