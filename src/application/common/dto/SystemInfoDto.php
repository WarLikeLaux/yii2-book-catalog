<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class SystemInfoDto
{
    public function __construct(
        public string $phpVersion,
        public string $yiiVersion,
        public string $dbDriver,
        public string $dbVersion,
    ) {
    }
}
