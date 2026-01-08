<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

final readonly class StorageConfig
{
    /**
     * Create an immutable storage configuration containing the filesystem base path and its public base URL.
     *
     * @param string $basePath Filesystem base path for storage.
     * @param string $baseUrl  Base URL used to access storage resources.
     */
    public function __construct(
        public string $basePath,
        public string $baseUrl,
    ) {
    }
}