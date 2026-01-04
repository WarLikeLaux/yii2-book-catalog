<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\domain\values\StoredFileReference;

final readonly class FileUrlResolver
{
    public function __construct(
        private string $baseUrl
    ) {
    }

    public function resolve(string|StoredFileReference|null $path): ?string
    {
        if ($path === null || (string)$path === '') {
            return null;
        }

        return $this->baseUrl . '/' . $path;
    }
}
