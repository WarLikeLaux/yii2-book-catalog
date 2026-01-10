<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\BuggregatorConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class BuggregatorConfigTest extends Unit
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = BuggregatorConfig::fromParams([
            'buggregator' => [
                'log' => [
                    'host' => 'buggregator',
                    'port' => 9913,
                ],
                'inspector' => [
                    'url' => 'http://buggregator:8000',
                    'ingestionKey' => 'key',
                ],
            ],
        ]);

        $this->assertSame('buggregator', $config->log->host);
        $this->assertSame(9913, $config->log->port);
        $this->assertSame('http://buggregator:8000', $config->inspector->url);
        $this->assertSame('key', $config->inspector->ingestionKey);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: buggregator');

        BuggregatorConfig::fromParams([]);
    }

    public function testFromParamsThrowsWhenLogMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: buggregator.log');

        BuggregatorConfig::fromParams([
            'buggregator' => [
                'inspector' => [
                    'url' => 'http://buggregator:8000',
                    'ingestionKey' => 'key',
                ],
            ],
        ]);
    }

    public function testFromParamsThrowsWhenInspectorMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: buggregator.inspector');

        BuggregatorConfig::fromParams([
            'buggregator' => [
                'log' => [
                    'host' => 'buggregator',
                    'port' => 9913,
                ],
            ],
        ]);
    }

    public function testFromParamsThrowsWhenLogHasNonStringKeys(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.log');

        BuggregatorConfig::fromParams([
            'buggregator' => [
                'log' => [
                    'host' => 'buggregator',
                    'port' => 9913,
                    0 => 'extra',
                ],
                'inspector' => [
                    'url' => 'http://buggregator:8000',
                    'ingestionKey' => 'key',
                ],
            ],
        ]);
    }
}
