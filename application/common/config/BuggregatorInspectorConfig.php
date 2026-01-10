<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class BuggregatorInspectorConfig
{
    public function __construct(
        public string $url,
        public string $ingestionKey,
    ) {
        if (trim($this->url) === '') {
            throw new ConfigurationException('Invalid config: buggregator.inspector.url');
        }

        if (trim($this->ingestionKey) === '') {
            throw new ConfigurationException('Invalid config: buggregator.inspector.ingestionKey');
        }
    }

    /**
     * @return array{url: string, ingestionKey: string}
     * @codeCoverageIgnore
     */
    public function __debugInfo(): array
    {
        $keyLength = strlen($this->ingestionKey);
        $maskedKey = $keyLength >= 12
        ? substr($this->ingestionKey, 0, 4) . '****' . substr($this->ingestionKey, -4)
        : '****';

        return [
            'url' => $this->url,
            'ingestionKey' => $maskedKey,
        ];
    }
}
