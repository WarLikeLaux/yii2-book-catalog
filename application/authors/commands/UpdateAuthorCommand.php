<?php

declare(strict_types=1);

namespace app\application\authors\commands;

use app\application\ports\CommandInterface;

final readonly class UpdateAuthorCommand implements CommandInterface
{
    public function __construct(
        public int $id,
        public string $fio,
    ) {
    }
}
