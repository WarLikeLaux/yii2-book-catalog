<?php

declare(strict_types=1);

namespace app\application\ports;

interface BookIsbnCheckerInterface
{
    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
