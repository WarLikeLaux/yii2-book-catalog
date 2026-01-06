<?php

declare(strict_types=1);

namespace app\application\common\middleware;

use app\application\ports\CommandInterface;
use app\application\ports\MiddlewareInterface;
use app\application\ports\TransactionInterface;
use Throwable;

final readonly class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TransactionInterface $transaction,
    ) {
    }

    public function process(CommandInterface $command, callable $next): mixed
    {
        $this->transaction->begin();

        try {
            $result = $next($command);
            $this->transaction->commit();

            return $result;
        } catch (Throwable $e) {
            $this->transaction->rollBack();

            throw $e;
        }
    }
}
