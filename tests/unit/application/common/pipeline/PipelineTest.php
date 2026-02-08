<?php

declare(strict_types=1);

namespace tests\unit\application\common\pipeline;

use app\application\common\exceptions\ApplicationException;
use app\application\common\pipeline\Pipeline;
use app\application\ports\CommandInterface;
use app\application\ports\MiddlewareInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Codeception\Test\Unit;

final class PipelineTest extends Unit
{
    public function testExecuteCallsUseCaseDirectlyWithoutMiddleware(): void
    {
        $pipeline = new Pipeline();
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $expectedResult = 'result';

        $useCase->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($expectedResult);

        $result = $pipeline->execute($command, $useCase);

        $this->assertSame($expectedResult, $result);
    }

    public function testExecutePassesCommandThroughSingleMiddleware(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $middlewareCalled = false;

        $middleware = new class ($middlewareCalled) implements MiddlewareInterface {
            public function __construct(private bool &$called)
            {
            }

            public function process(CommandInterface $command, callable $next): mixed
            {
                $this->called = true;

                return $next($command);
            }
        };

        $useCase->method('execute')->willReturn('result');

        $pipeline = (new Pipeline())->pipe($middleware);
        $pipeline->execute($command, $useCase);

        $this->assertTrue($middlewareCalled);
    }

    public function testExecuteRunsMiddlewareInCorrectOrder(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $order = [];

        $m1 = $this->createOrderTrackingMiddleware($order, '1');
        $m2 = $this->createOrderTrackingMiddleware($order, '2');
        $m3 = $this->createOrderTrackingMiddleware($order, '3');

        $useCase->method('execute')->willReturnCallback(static function () use (&$order): string {
            $order[] = 'usecase';

            return 'result';
        });

        $pipeline = (new Pipeline())->pipe($m1)->pipe($m2)->pipe($m3);
        $pipeline->execute($command, $useCase);

        $this->assertSame(
            ['1-before', '2-before', '3-before', 'usecase', '3-after', '2-after', '1-after'],
            $order,
        );
    }

    public function testPipeReturnsNewImmutablePipeline(): void
    {
        $pipeline1 = new Pipeline();
        $middleware = $this->createMock(MiddlewareInterface::class);

        $pipeline2 = $pipeline1->pipe($middleware);

        $this->assertNotSame($pipeline1, $pipeline2);
    }

    public function testExecuteReturnsUseCaseResult(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $expectedResult = ['id' => 42, 'name' => 'test'];

        $middleware = new class implements MiddlewareInterface {
            public function process(CommandInterface $command, callable $next): mixed
            {
                return $next($command);
            }
        };

        $useCase->method('execute')->willReturn($expectedResult);

        $pipeline = (new Pipeline())->pipe($middleware);
        $result = $pipeline->execute($command, $useCase);

        $this->assertSame($expectedResult, $result);
    }

    public function testMiddlewareCanModifyResult(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);

        $middleware = new class implements MiddlewareInterface {
            public function process(CommandInterface $command, callable $next): mixed
            {
                $result = $next($command);

                return $result . '-modified';
            }
        };

        $useCase->method('execute')->willReturn('original');

        $pipeline = (new Pipeline())->pipe($middleware);
        $result = $pipeline->execute($command, $useCase);

        $this->assertSame('original-modified', $result);
    }

    public function testExecuteWrapsDomainException(): void
    {
        $pipeline = new Pipeline();
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);

        $useCase->method('execute')
            ->willThrowException(new ValidationException(DomainErrorCode::BookTitleEmpty));

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookTitleEmpty->value);

        $pipeline->execute($command, $useCase);
    }

    /**
     * @param array<string> $order
     */
    private function createOrderTrackingMiddleware(array &$order, string $id): MiddlewareInterface
    {
        return new class ($order, $id) implements MiddlewareInterface {
            /**
             * @param array<string> $order
             */
            public function __construct(
                private array &$order,
                private readonly string $id,
            ) {
            }

            public function process(CommandInterface $command, callable $next): mixed
            {
                $this->order[] = $this->id . '-before';
                $result = $next($command);
                $this->order[] = $this->id . '-after';

                return $result;
            }
        };
    }
}
