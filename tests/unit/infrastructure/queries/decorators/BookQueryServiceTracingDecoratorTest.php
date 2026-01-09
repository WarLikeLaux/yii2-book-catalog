<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\queries\decorators;

use app\application\ports\BookQueryServiceInterface;
use app\application\ports\TracerInterface;
use app\infrastructure\queries\decorators\BookQueryServiceTracingDecorator;
use Codeception\Test\Unit;

final class BookQueryServiceTracingDecoratorTest extends Unit
{
    public function testGetReferencedCoverKeysDelegatesToService(): void
    {
        $expectedKeys = ['abc123', 'def456'];

        $service = $this->createMock(BookQueryServiceInterface::class);
        $service->expects($this->once())
            ->method('getReferencedCoverKeys')
            ->willReturn($expectedKeys);

        $tracer = $this->createMock(TracerInterface::class);
        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::getReferencedCoverKeys',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn($_, $callback) => $callback());

        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $result = $decorator->getReferencedCoverKeys();

        $this->assertSame($expectedKeys, $result);
    }

    public function testExistsByIsbnDelegatesToService(): void
    {
        $isbn = '978-3-16-148410-0';
        $expectedResult = true;

        $service = $this->createMock(BookQueryServiceInterface::class);
        $service->expects($this->once())
            ->method('existsByIsbn')
            ->with($isbn)
            ->willReturn($expectedResult);

        $tracer = $this->createMock(TracerInterface::class);
        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::existsByIsbn',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn($_, $callback) => $callback());

        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $result = $decorator->existsByIsbn($isbn);

        $this->assertSame($expectedResult, $result);
    }
}
