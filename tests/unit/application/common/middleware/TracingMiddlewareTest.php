<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\middleware\TracingMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\TracerInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class TracingMiddlewareTest extends Unit
{
    private TracerInterface&MockObject $tracer;
    private TracingMiddleware $middleware;

    protected function _before(): void
    {
        $this->tracer = $this->createMock(TracerInterface::class);
        $this->middleware = new TracingMiddleware($this->tracer);
    }

    public function testProcessTracesExecutionWithSpanName(): void
    {
        $command = new class implements CommandInterface {
        };
        $expectedResult = 'test-result';

        $this->tracer->expects($this->once())
            ->method('trace')
            ->willReturnCallback(
                function (string $spanName, callable $callback, array $attributes) use ($expectedResult): mixed {
                    $this->assertStringContainsString('UseCase::', $spanName);
                    $this->assertArrayHasKey('command.class', $attributes);

                    return $callback();
                },
            );

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessStripsCommandSuffixFromSpanName(): void
    {
        $command = new TestCommand();

        $this->tracer->expects($this->once())
            ->method('trace')
            ->willReturnCallback(
                function (string $spanName, callable $callback, array $_attributes): mixed {
                    $this->assertSame('UseCase::Test', $spanName);

                    return $callback();
                },
            );

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): bool => true,
        );
    }

    public function testProcessPassesCommandToNextHandler(): void
    {
        $command = new TestCommand();
        $passedCommand = null;

        $this->tracer->method('trace')
            ->willReturnCallback(static fn(string $_n, callable $cb, array $_a): mixed => $cb());

        $this->middleware->process(
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
