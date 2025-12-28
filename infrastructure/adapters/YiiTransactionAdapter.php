<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\TransactionInterface;
use yii\db\Connection;
use yii\db\Transaction;

final class YiiTransactionAdapter implements TransactionInterface
{
    /*
     * TODO: адаптер хранит состояние ($transaction).
     * В long-running процессах (Swoole/RoadRunner) убедитесь, что сервис не Singleton,
     * или корректно сбрасывайте стейт.
     */
    private Transaction|null $transaction = null;

    public function __construct(
        private readonly Connection $db
    ) {
    }

    public function begin(): void
    {
        $this->transaction = $this->db->beginTransaction();
    }

    public function commit(): void
    {
        if (!$this->transaction instanceof Transaction) {
            throw new \RuntimeException('Transaction not started');
        }

        $this->transaction->commit();
        $this->transaction = null;
    }

    public function rollBack(): void
    {
        if (!$this->transaction instanceof Transaction) {
            throw new \RuntimeException('Transaction not started');
        }

        $this->transaction->rollBack();
        $this->transaction = null;
    }
}
