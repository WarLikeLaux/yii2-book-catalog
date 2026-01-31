<?php

declare(strict_types=1);

namespace app\presentation\auth\dto;

final readonly class ApiInfoViewModel
{
    public function __construct(
        public int $swaggerPort,
        public int $appPort,
        public string $host,
    ) {
    }
}
