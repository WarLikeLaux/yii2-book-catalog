<?php

declare(strict_types=1);

namespace app\domain\repositories;

use app\domain\entities\Book;

interface BookRepositoryInterface
{
    public function save(Book $book): int;

    public function get(int $id): Book;

    public function getByIdAndVersion(int $id, int $expectedVersion): Book;

    public function delete(Book $book): void;
}
