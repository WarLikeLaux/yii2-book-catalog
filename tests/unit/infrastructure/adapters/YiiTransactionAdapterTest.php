<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiTransactionAdapter;
use PHPUnit\Framework\TestCase;
use yii\db\Connection;
use yii\db\Transaction;

final class YiiTransactionAdapterTest extends TestCase
{
    private Connection $db;
    private YiiTransactionAdapter $adapter;

    protected function setUp(): void
    {
        $this->db = $this->createStub(Connection::class);
        $this->adapter = new YiiTransactionAdapter($this->db);
    }

    public function testBeginStartsTransaction(): void
    {
        $db = $this->createMock(Connection::class);
        $transaction = $this->createStub(Transaction::class);
        $db->expects($this->once())
            ->method('beginTransaction')
            ->willReturn($transaction);

        $adapter = new YiiTransactionAdapter($db);
        $adapter->begin();
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
        $transaction = $this->createStub(Transaction::class);
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
        $transaction = $this->createStub(Transaction::class);
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
        $transaction = $this->createStub(Transaction::class);
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
        $db = $this->createMock(Connection::class);
        $activeTransaction = $this->createMock(Transaction::class);
        $activeTransaction->method('getIsActive')->willReturn(true);

        $db->method('getTransaction')->willReturn($activeTransaction);

        $adapter1 = new YiiTransactionAdapter($db);

        $db->expects($this->never())->method('beginTransaction');
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
