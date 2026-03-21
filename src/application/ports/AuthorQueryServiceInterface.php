<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\authors\queries\AuthorReadDto;
use app\application\common\dto\SortRequest;

interface AuthorQueryServiceInterface
{
    public function findById(int $id): ?AuthorReadDto;

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array;

    public function search(string $search, int $page, int $limit, ?SortRequest $sort = null): PagedResultInterface;

    public function searchWithFilters(
        ?int $id,
        string $fio,
        int $page,
        int $limit,
        ?SortRequest $sort = null,
    ): PagedResultInterface;
}
