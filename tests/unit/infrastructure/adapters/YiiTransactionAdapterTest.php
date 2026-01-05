<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiTransactionAdapter;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\db\Connection;
use yii\db\Transaction;

final class YiiTransactionAdapterTest extends Unit
{
    private Connection&MockObject $db;
    private YiiTransactionAdapter $adapter;

    protected function _before(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->adapter = new YiiTransactionAdapter($this->db);
    }

    public function testBeginStartsTransaction(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $this->db->expects($this->once())
            ->method('beginTransaction')
            ->willReturn($transaction);

        $this->adapter->begin();
    }

    public function testCommitThrowsExceptionWhenNotStarted(): void
    {
        $this->expectException('RuntimeException'::class);
        $this->expectExceptionMessage('Transaction not started');

        $this->adapter->commit();
    }

    public function testRollbackThrowsExceptionWhenNotStarted(): void
    {
        $this->expectException('RuntimeException'::class);
        $this->expectExceptionMessage('Transaction not started');

        $this->adapter->rollback();
    }

    public function testCommitSuccess(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('commit');
        $transaction->method('getIsActive')->willReturn(true);

        $this->db->method('beginTransaction')->willReturn($transaction);

        $this->adapter->begin();
        $this->adapter->commit();
    }

    public function testRollbackSuccess(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('rollBack');
        $transaction->method('getIsActive')->willReturn(true);

        $this->db->method('beginTransaction')->willReturn($transaction);

        $this->adapter->begin();
        $this->adapter->rollback();
    }

    public function testAfterCommitCallbacksExecutedAfterCommit(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getIsActive')->willReturn(true);
        $this->db->method('beginTransaction')->willReturn($transaction);

        $executed = false;
        $this->adapter->begin();
        $this->adapter->afterCommit(static function () use (&$executed): void {
            $executed = true;
        });
        $this->adapter->commit();

        $this->assertTrue($executed);
    }

    public function testAfterCommitCallbacksNotExecutedOnRollback(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getIsActive')->willReturn(true);
        $this->db->method('beginTransaction')->willReturn($transaction);

        $executed = false;
        $this->adapter->begin();
        $this->adapter->afterCommit(static function () use (&$executed): void {
            $executed = true;
        });
        $this->adapter->rollBack();

        $this->assertFalse($executed);
    }

    public function testMultipleAfterCommitCallbacksExecutedInOrder(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getIsActive')->willReturn(true);
        $this->db->method('beginTransaction')->willReturn($transaction);

        $order = [];
        $this->adapter->begin();
        $this->adapter->afterCommit(static function () use (&$order): void {
            $order[] = 'first';
        });
        $this->adapter->afterCommit(static function () use (&$order): void {
            $order[] = 'second';
        });
        $this->adapter->commit();

        $this->assertSame(['first', 'second'], $order);
    }

    public function testNestedTransactionsWithSameInstance(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('commit');
        $transaction->method('getIsActive')->willReturn(true);
        $this->db->method('beginTransaction')->willReturn($transaction);

        $this->adapter->begin();

        $this->adapter->begin();
        $this->adapter->commit();

        $this->adapter->commit();
    }

    public function testNestedTransactionsWithDifferentInstancesOnSameConnection(): void
    {
        $activeTransaction = $this->createMock(Transaction::class);
        $activeTransaction->method('getIsActive')->willReturn(true);

        $this->db->method('getTransaction')->willReturn($activeTransaction);

        $adapter1 = new YiiTransactionAdapter($this->db);

        $this->db->expects($this->never())->method('beginTransaction');
        $activeTransaction->expects($this->never())->method('commit');

        $adapter1->begin();
        $adapter1->commit();
    }

    public function testRollbackResetsNestingLevel(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getIsActive')->willReturn(true);
        $this->db->method('beginTransaction')->willReturn($transaction);

        $transaction->expects($this->once())->method('rollBack');

        $this->adapter->begin();
        $this->adapter->begin();

        $this->adapter->rollBack();

        $this->expectException('RuntimeException'::class);
        $this->expectExceptionMessage('Transaction not started');
        $this->adapter->commit();
    }
}
