<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\middleware\FileLifecycleMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\FileStorageInterface;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class FileLifecycleMiddlewareTest extends Unit
{
    private FileStorageInterface&MockObject $fileStorage;
    private FileLifecycleMiddleware $middleware;

    protected function _before(): void
    {
        $this->fileStorage = $this->createMock(FileStorageInterface::class);
        $this->middleware = new FileLifecycleMiddleware($this->fileStorage);
    }

    public function testProcessReturnsResultOnSuccess(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $expectedResult = 'success';

        $this->fileStorage->expects($this->never())->method('delete');

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessDoesNotDeleteFileOnSuccessWithCover(): void
    {
        $fileRef = new StoredFileReference('/uploads/test.jpg');
        $command = $this->createCommandWithCover($fileRef);

        $this->fileStorage->expects($this->never())->method('delete');

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => 'success',
        );
    }

    public function testProcessDeletesFileOnException(): void
    {
        $fileRef = new StoredFileReference('/uploads/test.jpg');
        $command = $this->createCommandWithCover($fileRef);

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with('/uploads/test.jpg');

        $this->expectException(RuntimeException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw new RuntimeException('Test error'),
        );
    }

    public function testProcessDoesNotDeleteFileWhenCommandHasNoCover(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $this->fileStorage->expects($this->never())->method('delete');

        $this->expectException(RuntimeException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw new RuntimeException('Test error'),
        );
    }

    public function testProcessDoesNotDeleteFileWhenCoverIsNull(): void
    {
        $command = $this->createCommandWithCover(null);

        $this->fileStorage->expects($this->never())->method('delete');

        $this->expectException(RuntimeException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw new RuntimeException('Test error'),
        );
    }

    public function testProcessDoesNotDeleteFileWhenCoverIsString(): void
    {
        $command = $this->createCommandWithCover('/string/path.jpg');

        $this->fileStorage->expects($this->never())->method('delete');

        $this->expectException(RuntimeException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw new RuntimeException('Test error'),
        );
    }

    public function testProcessRethrowsException(): void
    {
        $fileRef = new StoredFileReference('/uploads/test.jpg');
        $command = $this->createCommandWithCover($fileRef);
        $originalException = new RuntimeException('Original error');

        $this->fileStorage->method('delete');

        try {
            $this->middleware->process(
                $command,
                static fn(CommandInterface $_cmd) => throw $originalException,
            );
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertSame($originalException, $e);
        }
    }

    private function createCommandWithCover(mixed $cover): CommandInterface
    {
        return new class ($cover) implements CommandInterface {
            public function __construct(public readonly mixed $cover)
            {
            }
        };
    }
}
