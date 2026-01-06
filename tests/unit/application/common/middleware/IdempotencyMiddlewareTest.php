<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyKeyStatus;
use app\application\common\IdempotencyServiceInterface;
use app\application\common\middleware\IdempotencyMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\IdempotentCommandInterface;
use app\domain\exceptions\BusinessRuleException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class IdempotencyMiddlewareTest extends Unit
{
    private IdempotencyServiceInterface&MockObject $idempotencyService;
    private IdempotencyMiddleware $middleware;

    protected function _before(): void
    {
        $this->idempotencyService = $this->createMock(IdempotencyServiceInterface::class);
        $this->middleware = new IdempotencyMiddleware($this->idempotencyService);
    }

    public function testProcessSkipsNonIdempotentCommands(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $expectedResult = 'result';

        $this->idempotencyService->expects($this->never())->method('acquireLock');

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessThrowsWhenLockNotAcquired(): void
    {
        $command = $this->createIdempotentCommand('test-key');

        $this->idempotencyService->method('acquireLock')->willReturn(false);

        $this->expectException(BusinessRuleException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => 'result',
        );
    }

    public function testProcessReturnsCachedResultForFinishedRequest(): void
    {
        $command = $this->createIdempotentCommand('test-key');
        $cachedResult = ['result' => 'cached-value'];

        $this->idempotencyService->method('acquireLock')->willReturn(true);
        $this->idempotencyService->method('getRecord')->willReturn(
            new IdempotencyRecordDto(
                IdempotencyKeyStatus::Finished,
                200,
                $cachedResult,
                null,
            ),
        );
        $this->idempotencyService->expects($this->once())->method('releaseLock');

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => 'should-not-be-called',
        );

        $this->assertSame('cached-value', $result);
    }

    public function testProcessThrowsWhenStartRequestFails(): void
    {
        $command = $this->createIdempotentCommand('test-key');

        $this->idempotencyService->method('acquireLock')->willReturn(true);
        $this->idempotencyService->method('getRecord')->willReturn(null);
        $this->idempotencyService->method('startRequest')->willReturn(false);
        $this->idempotencyService->expects($this->atLeastOnce())->method('releaseLock');

        $this->expectException(BusinessRuleException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => 'result',
        );
    }

    public function testProcessExecutesAndSavesResult(): void
    {
        $command = $this->createIdempotentCommand('test-key');
        $expectedResult = 'new-result';

        $this->idempotencyService->method('acquireLock')->willReturn(true);
        $this->idempotencyService->method('getRecord')->willReturn(null);
        $this->idempotencyService->method('startRequest')->willReturn(true);
        $this->idempotencyService->expects($this->once())
            ->method('saveResponse')
            ->with('test-key', 200, ['result' => $expectedResult], null, $this->anything());
        $this->idempotencyService->expects($this->once())->method('releaseLock');

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessReleasesLockOnException(): void
    {
        $command = $this->createIdempotentCommand('test-key');

        $this->idempotencyService->method('acquireLock')->willReturn(true);
        $this->idempotencyService->method('getRecord')->willReturn(null);
        $this->idempotencyService->method('startRequest')->willReturn(true);
        $this->idempotencyService->expects($this->once())->method('releaseLock');

        $this->expectException(\RuntimeException::class);

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw new \RuntimeException('Test error'),
        );
    }

    private function createIdempotentCommand(string $key): IdempotentCommandInterface
    {
        return new class ($key) implements IdempotentCommandInterface {
            public function __construct(private readonly string $key)
            {
            }

            public function getIdempotencyKey(): string
            {
                return $this->key;
            }
        };
    }
}
