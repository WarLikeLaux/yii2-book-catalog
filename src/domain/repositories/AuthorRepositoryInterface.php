<?php

declare(strict_types=1);

namespace app\domain\repositories;

use app\domain\entities\Author;

interface AuthorRepositoryInterface
{
    public function save(Author $author): int;

    public function get(int $id): Author;

    public function delete(Author $author): void;
}
