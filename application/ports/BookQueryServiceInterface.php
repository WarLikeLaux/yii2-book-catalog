<?php

declare(strict_types=1);

namespace app\application\ports;

interface BookQueryServiceInterface extends BookFinderInterface, BookSearcherInterface
{
    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
