<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\TransactionInterface;
use RuntimeException;
use yii\db\Connection;
use yii\db\Transaction;

final class YiiTransactionAdapter implements TransactionInterface
{
    private Transaction|null $transaction = null;
    private int $nestingLevel = 0;
    private bool $isOwner = false;

    /** @var array<callable(): void> */
    private array $afterCommitCallbacks = [];

    public function __construct(
        private readonly Connection $db,
    ) {
    }

    public function begin(): void
    {
        if ($this->nestingLevel === 0) {
            $existingTransaction = $this->db->getTransaction();

            if ($existingTransaction !== null && $existingTransaction->getIsActive()) {
                $this->transaction = $existingTransaction;
                $this->isOwner = false;
            } else {
                $this->transaction = $this->db->beginTransaction();
                $this->isOwner = true;
            }
        }

        $this->nestingLevel++;
    }

    public function commit(): void
    {
        if ($this->nestingLevel === 0) {
            throw new RuntimeException('Transaction not started or nesting level mismatch');
        }

        $this->nestingLevel--;

        if ($this->nestingLevel !== 0) {
            return;
        }

        if ($this->isOwner) {
            if (!$this->transaction instanceof Transaction || !$this->transaction->getIsActive()) {
                throw new RuntimeException('Transaction not active during commit'); // @codeCoverageIgnore
            }

            $this->transaction->commit();
        }

        $this->transaction = null;
        $this->isOwner = false;

        $callbacks = $this->afterCommitCallbacks;
        $this->afterCommitCallbacks = [];

        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    public function rollBack(): void
    {
        if ($this->nestingLevel === 0) {
            throw new RuntimeException('Transaction not started');
        }

        if ($this->transaction instanceof Transaction && $this->transaction->getIsActive()) {
            $this->transaction->rollBack();
        }

        $this->transaction = null;
        $this->nestingLevel = 0;
        $this->isOwner = false;
        $this->afterCommitCallbacks = [];
    }

    public function afterCommit(callable $callback): void
    {
        $this->afterCommitCallbacks[] = $callback;
    }
}
