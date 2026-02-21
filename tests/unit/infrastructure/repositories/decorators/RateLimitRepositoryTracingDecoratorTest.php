<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories\decorators;

use app\application\common\dto\RateLimitResult;
use app\application\ports\RateLimitInterface;
use app\application\ports\TracerInterface;
use app\infrastructure\repositories\decorators\RateLimitRepositoryTracingDecorator;
use Codeception\Test\Unit;

final class RateLimitRepositoryTracingDecoratorTest extends Unit
{
    public function testCheckLimitDelegatesToDecoratedWithTracing(): void
    {
        $decorated = $this->createMock(RateLimitInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new RateLimitRepositoryTracingDecorator($decorated, $tracer);

        $expectedResult = new RateLimitResult(true, 5, 100, time() + 60);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'RateLimitRepository::checkLimit',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback): RateLimitResult => $callback());

        $decorated->expects($this->once())
            ->method('checkLimit')
            ->with('test_key', 100, 60)
            ->willReturn($expectedResult);

        $result = $decorator->checkLimit('test_key', 100, 60);

        $this->assertSame($expectedResult, $result);
    }
}
