<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class StorageConfig
{
    public function __construct(
        public string $basePath,
        public string $baseUrl,
        public string $placeholderUrl,
    ) {
        if (trim($this->basePath) === '') {
            throw new ConfigurationException('Invalid config: storage.basePath');
        }

        if (trim($this->baseUrl) === '') {
            throw new ConfigurationException('Invalid config: storage.baseUrl');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('storage');
        $basePath = $reader->requireString($section, 'storage', 'basePath');
        $baseUrl = $reader->requireString($section, 'storage', 'baseUrl');
        $placeholderUrl = $reader->requireString($section, 'storage', 'placeholderUrl');

        return new self($basePath, $baseUrl, $placeholderUrl);
    }
}
