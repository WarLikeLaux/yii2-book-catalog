<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class ReportsConfig
{
    private const int MIN_CACHE_TTL = 1;
    private const int MAX_CACHE_TTL = 86400;

    public function __construct(
        public int $cacheTtl,
    ) {
        if ($cacheTtl < self::MIN_CACHE_TTL || $cacheTtl > self::MAX_CACHE_TTL) {
            throw new ConfigurationException('Invalid config: reports.cacheTtl');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('reports');
        $cacheTtl = $reader->requireInt($section, 'reports', 'cacheTtl');

        return new self($cacheTtl);
    }
}
