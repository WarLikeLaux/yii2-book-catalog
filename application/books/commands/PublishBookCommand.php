<?php

declare(strict_types=1);

namespace app\application\books\commands;

final readonly class PublishBookCommand
{
    public function __construct(
        public int $bookId
    ) {
    }
}
