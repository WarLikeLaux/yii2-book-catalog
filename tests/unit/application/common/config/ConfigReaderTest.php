<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\ConfigReader;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class ConfigReaderTest extends Unit
{
    public function testRequireSubsection(): void
    {
        $reader = new ConfigReader(['test_section' => ['sub' => ['key' => 'value']]]);
        $section = $reader->requireSection('test_section');

        $result = $reader->requireSubsection($section, 'test_section', 'sub');
        $this->assertSame(['key' => 'value'], $result);
    }

    public function testRequireSubsectionNull(): void
    {
        $reader = new ConfigReader([]);
        $section = [];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: test_section.sub');
        $reader->requireSubsection($section, 'test_section', 'sub');
    }

    public function testRequireArraySectionNonStringKeys(): void
    {
        $reader = new ConfigReader(['test_section' => ['value1', 'value2']]); // int keys

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: test_section');
        $reader->requireSection('test_section');
    }
}
