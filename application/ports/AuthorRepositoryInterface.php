<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Author;

interface AuthorRepositoryInterface
{
    public function save(Author $author): void;

    public function get(int $id): Author;

    public function delete(Author $author): void;

    public function existsByFio(string $fio, ?int $excludeId = null): bool;
}
