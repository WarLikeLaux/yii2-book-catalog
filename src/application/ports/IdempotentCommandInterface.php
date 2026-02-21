<?php

declare(strict_types=1);

namespace app\application\ports;

interface IdempotentCommandInterface extends CommandInterface
{
    /**
     * @return non-empty-string
     */
    public function getIdempotencyKey(): string;
}
