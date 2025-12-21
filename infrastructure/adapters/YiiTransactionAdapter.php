<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\TransactionInterface;
use yii\db\Connection;

final class YiiTransactionAdapter implements TransactionInterface
{
    private ?\yii\db\Transaction $transaction = null;

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
        if ($this->transaction === null) {
            throw new \RuntimeException('Transaction not started');
        }
        $this->transaction->commit();
        $this->transaction = null;
    }

    public function rollBack(): void
    {
        if ($this->transaction === null) {
            return;
        }
        $this->transaction->rollBack();
        $this->transaction = null;
    }
}
