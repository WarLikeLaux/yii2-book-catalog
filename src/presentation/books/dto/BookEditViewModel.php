<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\application\books\queries\BookReadDto;
use app\presentation\books\forms\BookForm;
use app\presentation\common\ViewModelInterface;

final readonly class BookEditViewModel implements ViewModelInterface
{
    /**
     * @param array<int, string> $authors
     */
    public function __construct(
        public BookForm $form,
        public array $authors = [],
        public ?BookReadDto $book = null,
    ) {
    }
}
