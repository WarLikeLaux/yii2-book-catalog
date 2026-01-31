<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\application\books\queries\BookReadDto;

final readonly class BookViewViewModel
{
    public function __construct(
        public BookReadDto $book,
    ) {
    }
}
