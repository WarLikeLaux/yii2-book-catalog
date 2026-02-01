<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\application\books\queries\BookReadDto;
use app\presentation\common\ViewModelInterface;

final readonly class BookViewViewModel implements ViewModelInterface
{
    public function __construct(
        public BookReadDto $book,
    ) {
    }
}
