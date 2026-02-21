<?php

declare(strict_types=1);

namespace app\application\ports;

interface AuthorExistenceCheckerInterface
{
    public function existsByFio(string $fio, ?int $excludeId = null): bool;

    public function existsById(int $id): bool;

    /**
     * @param array<int> $ids
     */
    public function existsAllByIds(array $ids): bool;
}
