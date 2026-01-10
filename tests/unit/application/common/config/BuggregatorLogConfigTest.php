<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\BuggregatorLogConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class BuggregatorLogConfigTest extends Unit
{
    public function testConstructorThrowsWhenHostEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.log.host');

        $create = static fn() => new BuggregatorLogConfig('   ', 9913);
        $create();
    }

    public function testConstructorThrowsWhenPortTooSmall(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: buggregator.log.port');

        $create = static fn() => new BuggregatorLogConfig('buggregator', 0);
        $create();
    }
}
