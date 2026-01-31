<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use app\application\authors\queries\AuthorReadDto;

final readonly class AuthorViewViewModel
{
    public function __construct(
        public AuthorReadDto $author,
    ) {
    }
}
