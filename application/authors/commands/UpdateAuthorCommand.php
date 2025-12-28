<?php

declare(strict_types=1);

namespace app\application\authors\commands;

final readonly class UpdateAuthorCommand
{
    public function __construct(
        public int $id,
        public string $fio,
    ) {
    }
}
