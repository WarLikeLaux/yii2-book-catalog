<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\BuggregatorInspectorConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class BuggregatorInspectorConfigTest extends Unit
{
    public function testConstructorThrowsWhenUrlEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.inspector.url');

        $create = static fn() => new BuggregatorInspectorConfig(' ', 'key');
        $create();
    }

    public function testConstructorThrowsWhenIngestionKeyEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.inspector.ingestionKey');

        $create = static fn() => new BuggregatorInspectorConfig('http://buggregator:8000', '');
        $create();
    }

    public function testConstructorThrowsWhenIngestionKeyWhitespaceOnly(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.inspector.ingestionKey');

        $create = static fn() => new BuggregatorInspectorConfig('http://buggregator:8000', '   ');
        $create();
    }
}
