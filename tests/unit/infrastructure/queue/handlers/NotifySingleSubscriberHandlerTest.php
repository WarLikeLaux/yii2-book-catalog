<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue\handlers;

use app\application\ports\CacheInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use Codeception\Test\Unit;
use RuntimeException;

final class NotifySingleSubscriberHandlerTest extends Unit
{
    public function testHandleSendsSmsWhenTokenMatches(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->with('+7900', 'message');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('getOrSet')
            ->willReturnCallback(static fn(string $_key, callable $callback, int $_ttl): string => $callback());
        $cache->expects($this->never())
            ->method('delete');

        $handler = new NotifySingleSubscriberHandler($sender, $cache);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleSkipsWhenTokenAlreadyStored(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->never())
            ->method('send');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('getOrSet')
            ->willReturn('existing');
        $cache->expects($this->never())
            ->method('delete');

        $handler = new NotifySingleSubscriberHandler($sender, $cache);
        $handler->handle('+7900', 'message', 15);
    }

    public function testHandleDeletesTokenOnFailure(): void
    {
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('fail'));

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('getOrSet')
            ->willReturnCallback(static fn(string $_key, callable $callback, int $_ttl): string => $callback());
        $cache->expects($this->once())
            ->method('delete');

        $handler = new NotifySingleSubscriberHandler($sender, $cache);

        $this->expectException(RuntimeException::class);
        $handler->handle('+7900', 'message', 15);
    }
}
