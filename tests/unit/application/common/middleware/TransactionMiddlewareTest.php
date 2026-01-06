<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\middleware\TransactionMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\TransactionInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class TransactionMiddlewareTest extends Unit
{
    private TransactionInterface&MockObject $transaction;
    private TransactionMiddleware $middleware;

    protected function _before(): void
    {
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->middleware = new TransactionMiddleware($this->transaction);
    }

    public function testProcessCommitsOnSuccess(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $expectedResult = 'success';

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');
        $this->transaction->expects($this->never())->method('rollBack');

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessRollsBackOnException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $exception = new RuntimeException('Test error');

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw $exception,
        );
    }

    public function testProcessBeginsTransactionBeforeExecution(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $callOrder = [];

        $this->transaction->method('begin')
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'begin';
            });

        $this->transaction->method('commit')
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'commit';
            });

        $this->middleware->process(
            $command,
            static function (CommandInterface $_cmd) use (&$callOrder): string {
                $callOrder[] = 'execute';

                return 'done';
            },
        );

        $this->assertSame(['begin', 'execute', 'commit'], $callOrder);
    }

    public function testProcessRethrowsExceptionAfterRollback(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $exception = new RuntimeException('Original error');
        $thrownException = null;

        $this->transaction->method('begin');
        $this->transaction->method('rollBack');

        try {
            $this->middleware->process(
                $command,
                static fn(CommandInterface $_cmd) => throw $exception,
            );
        } catch (RuntimeException $e) {
            $thrownException = $e;
        }

        $this->assertSame($exception, $thrownException);
    }
}
