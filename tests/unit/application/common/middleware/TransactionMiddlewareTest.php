<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\middleware\TransactionMiddleware;
use app\application\ports\CommandInterface;
use app\application\ports\TransactionInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TransactionMiddlewareTest extends TestCase
{
    private TransactionInterface&Stub $transaction;
    private TransactionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->transaction = $this->createStub(TransactionInterface::class);
        $this->middleware = new TransactionMiddleware($this->transaction);
    }

    public function testProcessCommitsOnSuccess(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $expectedResult = 'success';

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('begin');
        $transaction->expects($this->once())->method('commit');
        $transaction->expects($this->never())->method('rollBack');

        $middleware = new TransactionMiddleware($transaction);
        $result = $middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => $expectedResult,
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessRollsBackOnException(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $exception = new RuntimeException('Test error');

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects($this->once())->method('begin');
        $transaction->expects($this->never())->method('commit');
        $transaction->expects($this->once())->method('rollBack');

        $middleware = new TransactionMiddleware($transaction);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $middleware->process(
            $command,
            static fn(CommandInterface $_cmd) => throw $exception,
        );
    }

    public function testProcessBeginsTransactionBeforeExecution(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $callOrder = [];

        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('begin')
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'begin';
            });

        $transaction->method('commit')
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'commit';
            });

        $middleware = new TransactionMiddleware($transaction);
        $middleware->process(
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
        $command = $this->createStub(CommandInterface::class);
        $exception = new RuntimeException('Original error');
        $thrownException = null;

        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('begin');
        $transaction->method('rollBack');

        $middleware = new TransactionMiddleware($transaction);

        try {
            $middleware->process(
                $command,
                static fn(CommandInterface $_cmd) => throw $exception,
            );
        } catch (RuntimeException $e) {
            $thrownException = $e;
        }

        $this->assertSame($exception, $thrownException);
    }
}
