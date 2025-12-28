<?php

declare(strict_types=1);

namespace app\application\ports;

interface TransactionInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollBack(): void;
}
