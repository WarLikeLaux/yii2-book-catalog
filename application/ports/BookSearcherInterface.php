<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\specifications\BookSpecificationInterface;

interface BookSearcherInterface
{
    public function search(string $term, int $page, int $limit): PagedResultInterface;

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $limit,
    ): PagedResultInterface;
}
