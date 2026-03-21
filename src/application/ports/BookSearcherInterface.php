<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookColumnFilterDto;
use app\domain\specifications\BookSpecificationInterface;

interface BookSearcherInterface
{
    public function search(string $term, int $page, int $limit): PagedResultInterface;

    public function searchPublished(string $term, int $page, int $limit): PagedResultInterface;

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $limit,
    ): PagedResultInterface;

    public function searchWithFilters(BookColumnFilterDto $filter, int $page, int $limit): PagedResultInterface;
}
