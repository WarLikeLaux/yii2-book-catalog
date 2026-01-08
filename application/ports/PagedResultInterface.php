<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\PaginationDto;

/**
 * @template T of object
 */
interface PagedResultInterface
{
    /**
 * Retrieve the models contained in this paged result.
 *
 * @return array<int, T> Indexed array of T model objects.
 */
    public function getModels(): array;

    public function getTotalCount(): int;

    public function getPagination(): ?PaginationDto;
}