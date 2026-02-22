<?php

declare(strict_types=1);

namespace app\application\books\queries;

final readonly class BookReadDto
{
    public function __construct(
        public int $id,
        public string $title,
    ) {
    }
}
