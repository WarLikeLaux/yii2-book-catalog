<?php

declare(strict_types=1);

namespace app\application\authors\queries;

final readonly class AuthorReadDto
{
    public function __construct(
        public int $id,
        public string $fio
    ) {
    }
}
