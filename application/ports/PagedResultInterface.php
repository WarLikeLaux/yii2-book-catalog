<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\PaginationDto;

interface PagedResultInterface
{
    /**
     * @return array<int, object>
     */
    public function getModels(): array;

    public function getTotalCount(): int;

    public function getPagination(): ?PaginationDto;
}
