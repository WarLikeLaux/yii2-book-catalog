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
        $this->expectException("RuntimeException"::class);
        $this->expectExceptionMessage('Transaction not started');

        $this->adapter->commit();
    }

    public function testRollbackThrowsExceptionWhenNotStarted(): void
    {
        $this->expectException("RuntimeException"::class);
        $this->expectExceptionMessage('Transaction not started');

        $this->adapter->rollback();
    }

    public function testCommitSuccess(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('commit');
        
        $this->db->method('beginTransaction')->willReturn($transaction);

        $this->adapter->begin();
        $this->adapter->commit();
    }

    public function testRollbackSuccess(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->once())->method('rollBack');
        
        $this->db->method('beginTransaction')->willReturn($transaction);

        $this->adapter->begin();
        $this->adapter->rollback();
    }
}
