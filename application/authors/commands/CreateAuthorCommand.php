<?php

declare(strict_types=1);

namespace app\application\authors\commands;

final readonly class CreateAuthorCommand
{
    public function __construct(
        public string $fio,
    ) {
    }
}
