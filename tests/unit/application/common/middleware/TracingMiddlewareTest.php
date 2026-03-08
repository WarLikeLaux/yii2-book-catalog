<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\middleware\TracingMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\TracerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class TracingMiddlewareTest extends TestCase
{
    private TracerInterface&Stub $tracer;
    private TracingMiddleware $middleware;

    protected function setUp(): void
    {
        $this->tracer = $this->createStub(TracerInterface::class);
        $this->middleware = new TracingMiddleware($this->tracer);
    }

    public function testProcessTracesExecutionWithSpanName(): void
    {
        $command = new class implements CommandInterface {
        };
        $expectedResult = 'test-result';

        $tracer = $this->createMock(TracerInterface::class);
        $tracer->expects($this->once())
            ->method('trace')
            ->willReturnCallback(
                function (string $spanName, callable $callback, array $attributes) use ($expectedResult): mixed {
                    $this->assertStringContainsString('UseCase::', $spanName);
                    $this->assertArrayHasKey('command.class', $attributes);

                    return $callback();
                },
            );

        $middleware = new TracingMiddleware($tracer);
        $result = $middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessStripsCommandSuffixFromSpanName(): void
    {
        $command = new TestCommand();

        $tracer = $this->createMock(TracerInterface::class);
        $tracer->expects($this->once())
            ->method('trace')
            ->willReturnCallback(
                function (string $spanName, callable $callback, array $_attributes): mixed {
                    $this->assertSame('UseCase::Test', $spanName);

                    return $callback();
                },
            );

        $middleware = new TracingMiddleware($tracer);
        $middleware->process(
            $command,
            static fn(CommandInterface $_cmd): bool => true,
        );
    }

    public function testProcessPassesCommandToNextHandler(): void
    {
        $command = new TestCommand();
        $passedCommand = null;

        $tracer = $this->createStub(TracerInterface::class);
        $tracer->method('trace')
            ->willReturnCallback(static fn(string $_n, callable $cb, array $_a): mixed => $cb());

        $middleware = new TracingMiddleware($tracer);
        $middleware->process(
            $command,
            static function (CommandInterface $cmd) use (&$passedCommand): bool {
                $passedCommand = $cmd;

                return true;
            },
        );

        $this->assertSame($command, $passedCommand);
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class TestCommand implements CommandInterface
{
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
