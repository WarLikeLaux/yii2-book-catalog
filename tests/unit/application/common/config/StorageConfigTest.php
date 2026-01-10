<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\StorageConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class StorageConfigTest extends Unit
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = StorageConfig::fromParams([
            'storage' => [
                'basePath' => '@app/web/uploads',
                'baseUrl' => '/uploads',
                'placeholderUrl' => 'https://example.com/{seed}',
            ],
        ]);

        $this->assertSame('@app/web/uploads', $config->basePath);
        $this->assertSame('/uploads', $config->baseUrl);
        $this->assertSame('https://example.com/{seed}', $config->placeholderUrl);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: storage');

        StorageConfig::fromParams([]);
    }

    public function testConstructorThrowsWhenBasePathEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: storage.basePath');

        $create = static fn() => new StorageConfig('', '/uploads', '');
        $create();
    }

    public function testConstructorThrowsWhenBaseUrlEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: storage.baseUrl');

        $create = static fn() => new StorageConfig('/uploads', ' ', '');
        $create();
    }

    public function testConstructorThrowsWhenBasePathWhitespaceOnly(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: storage.basePath');

        $create = static fn() => new StorageConfig('   ', '/uploads', '');
        $create();
    }
}
