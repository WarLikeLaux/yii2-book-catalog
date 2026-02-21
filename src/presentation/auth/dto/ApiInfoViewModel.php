<?php

declare(strict_types=1);

namespace app\presentation\auth\dto;

use app\presentation\common\ViewModelInterface;

final readonly class ApiInfoViewModel implements ViewModelInterface
{
    public function __construct(
        public int $swaggerPort,
        public int $appPort,
        public string $host,
    ) {
    }
}
