<?php

declare(strict_types=1);

namespace app\interfaces;

interface QueryResultInterface
{
    public function getModels(): array;

    public function getTotalCount(): int;

    public function getPagination(): ?object;
}
