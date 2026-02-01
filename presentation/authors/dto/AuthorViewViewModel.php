<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use app\application\authors\queries\AuthorReadDto;
use app\presentation\common\ViewModelInterface;

final readonly class AuthorViewViewModel implements ViewModelInterface
{
    public function __construct(
        public AuthorReadDto $author,
    ) {
    }
}
