<?php

declare(strict_types=1);

namespace app\application\common\config;

final readonly class BuggregatorConfig
{
    public function __construct(
        public BuggregatorLogConfig $log,
        public BuggregatorInspectorConfig $inspector,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('buggregator');
        $log = $reader->requireSubsection($section, 'buggregator', 'log');
        $inspector = $reader->requireSubsection($section, 'buggregator', 'inspector');

        $logConfig = new BuggregatorLogConfig(
            $reader->requireString($log, 'buggregator.log', 'host'),
            $reader->requireInt($log, 'buggregator.log', 'port'),
        );

        $inspectorConfig = new BuggregatorInspectorConfig(
            $reader->requireString($inspector, 'buggregator.inspector', 'url'),
            $reader->requireString($inspector, 'buggregator.inspector', 'ingestionKey'),
        );

        return new self($logConfig, $inspectorConfig);
    }
}
