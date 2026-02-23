<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories\decorators;

use app\application\ports\TracerInterface;
use app\domain\entities\Author;
use app\domain\repositories\AuthorRepositoryInterface;
use app\infrastructure\repositories\decorators\AuthorRepositoryTracingDecorator;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorRepositoryTracingDecoratorTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $innerRepository;
    private TracerInterface&MockObject $tracer;
    private AuthorRepositoryTracingDecorator $decorator;

    protected function _before(): void
    {
        $this->innerRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->tracer = $this->createMock(TracerInterface::class);
        $this->decorator = new AuthorRepositoryTracingDecorator($this->innerRepository, $this->tracer);
    }

    public function testRemoveAllBookLinksDelegatesToInnerWithTracing(): void
    {
        $this->tracer->expects($this->once())
            ->method('trace')
            ->with(
                'AuthorRepo::removeAllBookLinks',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback) => $callback());

        $this->innerRepository->expects($this->once())
            ->method('removeAllBookLinks')
            ->with(42);

        $this->decorator->removeAllBookLinks(42);
    }

    public function testSaveDelegatesToInnerWithTracing(): void
    {
        $author = Author::create('Test');

        $this->tracer->expects($this->once())
            ->method('trace')
            ->with(
                'AuthorRepo::save',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback): int => $callback());

        $this->innerRepository->expects($this->once())
            ->method('save')
            ->with($author)
            ->willReturn(1);

        $result = $this->decorator->save($author);

        $this->assertSame(1, $result);
    }

    public function testGetDelegatesToInnerWithTracing(): void
    {
        $author = Author::reconstitute(1, 'Test');

        $this->tracer->expects($this->once())
            ->method('trace')
            ->with(
                'AuthorRepo::get',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback): Author => $callback());

        $this->innerRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($author);

        $result = $this->decorator->get(1);

        $this->assertSame($author, $result);
    }

    public function testDeleteDelegatesToInnerWithTracing(): void
    {
        $author = Author::reconstitute(1, 'Test');

        $this->tracer->expects($this->once())
            ->method('trace')
            ->with(
                'AuthorRepo::delete',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_name, callable $callback) => $callback());

        $this->innerRepository->expects($this->once())
            ->method('delete')
            ->with($author);

        $this->decorator->delete($author);
    }
}
