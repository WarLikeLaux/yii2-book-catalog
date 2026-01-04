<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

final readonly class StorageConfig
{
    public function __construct(
        public string $basePath,
        public string $baseUrl,
        public string $tempBasePath,
        public string $tempBaseUrl
    ) {
    }
}
