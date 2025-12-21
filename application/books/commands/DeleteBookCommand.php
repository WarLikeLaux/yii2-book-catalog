<?php

declare(strict_types=1);

namespace app\application\books\commands;

final readonly class DeleteBookCommand
{
    public function __construct(
        public int $id,
    ) {
    }
}
