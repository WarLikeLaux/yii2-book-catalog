<?php

declare(strict_types=1);

namespace app\application\books\queries;

final readonly class BookColumnFilterDto
{
    public function __construct(
        public ?int $id = null,
        public ?string $title = null,
        public ?int $year = null,
        public ?string $isbn = null,
        public ?string $status = null,
        public ?string $author = null,
    ) {
    }
}
