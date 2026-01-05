<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class TemporaryFile
{
    public function __construct(
        public string $tempPath,
        public string $filename,
    ) {
    }
}
