<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\domain\values\StoredFileReference;

final readonly class FileUrlResolver
{
    private const int PLACEHOLDER_SEED_MOD = 1000;

    public function __construct(
        private string $baseUrl,
        private string $placeholderUrl = '',
    ) {
    }

    public function resolve(string|StoredFileReference|null $path): ?string
    {
        if ($path === null || (string)$path === '') {
            return null;
        }

        return $this->baseUrl . '/' . $path;
    }

    public function resolveCoverUrl(?string $coverUrl, int $entityId): string
    {
        if ($coverUrl !== null && $coverUrl !== '') {
            $resolved = $this->resolve($coverUrl);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        if ($this->placeholderUrl === '') {
            return '';
        }

        $seed = abs($entityId) % self::PLACEHOLDER_SEED_MOD;
        return str_replace('{seed}', (string)$seed, $this->placeholderUrl);
    }
}
