<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class UploadedFilePayload
{
    public function __construct(
        public string $path,
        public string $extension,
        public string $mimeType,
    ) {
    }
}
