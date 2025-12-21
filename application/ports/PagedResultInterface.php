<?php

declare(strict_types=1);

namespace app\application\ports;

interface PagedResultInterface
{
    public function getModels(): array;

    public function getTotalCount(): int;

    public function getPagination(): ?object;
}
