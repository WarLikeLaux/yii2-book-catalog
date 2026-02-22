<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiCacheAdapter;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\caching\CacheInterface as YiiCache;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection as RedisConnection;

final class YiiCacheAdapterTest extends Unit
{
    private YiiCache&MockObject $yiiCache;
    private YiiCacheAdapter $adapter;

    protected function _before(): void
    {
        $this->yiiCache = $this->createMock(YiiCache::class);
        $this->adapter = new YiiCacheAdapter($this->yiiCache);
    }

    public function testGetOrSetDelegatesToYiiCache(): void
    {
        $callback = static fn(): string => 'value';

        $this->yiiCache->expects($this->once())
            ->method('getOrSet')
            ->with('key', $callback, 3600)
            ->willReturn('cached_value');

        $result = $this->adapter->getOrSet('key', $callback);

        $this->assertSame('cached_value', $result);
    }

    public function testGetOrSetWithCustomTtl(): void
    {
        $callback = static fn(): string => 'value';

        $this->yiiCache->expects($this->once())
            ->method('getOrSet')
            ->with('key', $callback, 7200)
            ->willReturn('cached_value');

        $result = $this->adapter->getOrSet('key', $callback, 7200);

        $this->assertSame('cached_value', $result);
    }

    public function testDeleteDelegatesToYiiCache(): void
    {
        $this->yiiCache->expects($this->once())
            ->method('delete')
            ->with('key');

        $this->adapter->delete('key');
    }

    public function testDeleteByPrefixDoesNothingForNonRedisCache(): void
    {
        $this->adapter->deleteByPrefix('prefix:');

        $this->assertTrue(true);
    }

    public function testDeleteByPrefixDoesNothingForNonConnectionRedis(): void
    {
        /** @var RedisCache&MockObject $redisCache */
        $redisCache = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisCache->redis = 'not-a-connection';

        $adapter = new YiiCacheAdapter($redisCache);
        $adapter->deleteByPrefix('prefix:');

        $this->assertTrue(true);
    }

    public function testDeleteByPrefixUsesRedisScan(): void
    {
        $redisConnection = $this->createMock(RedisConnection::class);

        /** @var RedisCache&MockObject $redisCache */
        $redisCache = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisCache->redis = $redisConnection;
        $redisCache->keyPrefix = 'yii:';

        $callCount = 0;
        $redisConnection->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(static function (string $command) use (&$callCount): mixed {
                $callCount++;

                if ($command === 'SCAN') {
                    return ['0', ['yii:prefix:key1']];
                }

                return 1;
            });

        $adapter = new YiiCacheAdapter($redisCache);
        $adapter->deleteByPrefix('prefix:');

        $this->assertSame(2, $callCount);
    }

    public function testDeleteByPrefixSkipsEmptyKeys(): void
    {
        $redisConnection = $this->createMock(RedisConnection::class);

        /** @var RedisCache&MockObject $redisCache */
        $redisCache = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisCache->redis = $redisConnection;
        $redisCache->keyPrefix = '';

        $redisConnection->expects($this->once())
            ->method('executeCommand')
            ->with('SCAN', $this->anything())
            ->willReturn(['0', []]);

        $adapter = new YiiCacheAdapter($redisCache);
        $adapter->deleteByPrefix('test:');

        $this->assertTrue(true);
    }

    public function testDeleteByPrefixWithMultipleIterations(): void
    {
        $redisConnection = $this->createMock(RedisConnection::class);

        /** @var RedisCache&MockObject $redisCache */
        $redisCache = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redisCache->redis = $redisConnection;
        $redisCache->keyPrefix = '';

        $scanCount = 0;
        $redisConnection->expects($this->exactly(4))
            ->method('executeCommand')
            ->willReturnCallback(static function (string $command) use (&$scanCount): mixed {
                if ($command === 'SCAN') {
                    $scanCount++;

                    if ($scanCount === 1) {
                        return ['5', ['key1']];
                    }

                    return ['0', ['key2']];
                }

                return 1;
            });

        $adapter = new YiiCacheAdapter($redisCache);
        $adapter->deleteByPrefix('test:');

        $this->assertSame(2, $scanCount);
    }
}
