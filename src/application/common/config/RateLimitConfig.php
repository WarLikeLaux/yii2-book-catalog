<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class RateLimitConfig
{
    public function __construct(
        public int $limit,
        public int $window,
    ) {
        if ($this->limit < 1) {
            throw new ConfigurationException('Invalid config: rateLimit.limit');
        }

        if ($this->window < 1) {
            throw new ConfigurationException('Invalid config: rateLimit.window');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('rateLimit');
        $limit = $reader->requireInt($section, 'rateLimit', 'limit');
        $window = $reader->requireInt($section, 'rateLimit', 'window');

        return new self($limit, $window);
    }
}
