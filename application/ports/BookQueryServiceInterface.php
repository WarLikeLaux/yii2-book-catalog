<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookReadDto;
use app\domain\specifications\BookSpecificationInterface;

interface BookQueryServiceInterface
{
    public function findById(int $id): ?BookReadDto;

    public function findByIdWithAuthors(int $id): ?BookReadDto;

    public function search(string $term, int $page, int $pageSize): PagedResultInterface;

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $pageSize,
    ): PagedResultInterface;
}
