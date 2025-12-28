<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\PaginationDto;

interface PagedResultInterface
{
    /**
     * @return array<mixed>
     */
    public function getModels(): array;

    public function getTotalCount(): int;

    public function getPagination(): ?PaginationDto;
}
