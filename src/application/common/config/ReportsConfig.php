<?php

declare(strict_types=1);

namespace app\application\common\config;

final readonly class ReportsConfig
{
    public function __construct(
        public int $cacheTtl,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('reports');
        $cacheTtl = $reader->requireInt($section, 'reports', 'cacheTtl');

        if ($cacheTtl < 1 || $cacheTtl > 86400) {
            $cacheTtl = 3600;
        }

        return new self($cacheTtl);
    }
}
