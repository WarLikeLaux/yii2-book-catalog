<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories\decorators;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyKeyStatus;
use app\application\ports\IdempotencyInterface;
use app\application\ports\TracerInterface;
use app\infrastructure\repositories\decorators\IdempotencyRepositoryTracingDecorator;
use Codeception\Test\Unit;

final class IdempotencyRepositoryTracingDecoratorTest extends Unit
{
    public function testGetRecordDelegatesToDecoratedWithTracing(): void
    {
        $decorated = $this->createMock(IdempotencyInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new IdempotencyRepositoryTracingDecorator($decorated, $tracer);

        $expectedResult = new IdempotencyRecordDto(IdempotencyKeyStatus::Finished, 200, ['id' => 1], null);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'IdempotencyRepo::getRecord',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback) => $callback());

        $decorated->expects($this->once())
            ->method('getRecord')
            ->with('test-key')
            ->willReturn($expectedResult);

        $result = $decorator->getRecord('test-key');

        $this->assertSame($expectedResult, $result);
    }

    public function testGetRecordReturnsNullWhenDecoratedReturnsNull(): void
    {
        $decorated = $this->createMock(IdempotencyInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new IdempotencyRepositoryTracingDecorator($decorated, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->willReturnCallback(static fn(string $_name, callable $callback) => $callback());

        $decorated->expects($this->once())
            ->method('getRecord')
            ->with('missing-key')
            ->willReturn(null);

        $result = $decorator->getRecord('missing-key');

        $this->assertNull($result);
    }

    public function testSaveStartedDelegatesToDecoratedWithTracing(): void
    {
        $decorated = $this->createMock(IdempotencyInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new IdempotencyRepositoryTracingDecorator($decorated, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'IdempotencyRepo::saveStarted',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback): bool => $callback());

        $decorated->expects($this->once())
            ->method('saveStarted')
            ->with('key', 3600)
            ->willReturn(true);

        $result = $decorator->saveStarted('key', 3600);

        $this->assertTrue($result);
    }

    public function testSaveResponseDelegatesToDecoratedWithTracing(): void
    {
        $decorated = $this->createMock(IdempotencyInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new IdempotencyRepositoryTracingDecorator($decorated, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'IdempotencyRepo::saveResponse',
                $this->isType('callable'),
            )
            ->willReturnCallback(static function (string $_name, callable $callback): void {
                $callback();
            });

        $decorated->expects($this->once())
            ->method('saveResponse')
            ->with('key', 201, '{"id":1}', 3600);

        $decorator->saveResponse('key', 201, '{"id":1}', 3600);
    }
}
