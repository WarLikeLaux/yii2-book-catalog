<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class BuggregatorLogConfig
{
    public function __construct(
        public string $host,
        public int $port,
    ) {
        if (trim($this->host) === '') {
            throw new ConfigurationException('Invalid config: buggregator.log.host');
        }

        if ($this->port < 1) {
            throw new ConfigurationException('Invalid config: buggregator.log.port');
        }
    }
}
