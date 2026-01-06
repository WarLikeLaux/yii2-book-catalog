<?php

declare(strict_types=1);

namespace app\domain\exceptions;

use RuntimeException;
use Throwable;

abstract class DomainException extends RuntimeException
{
    public function __construct(
        public readonly DomainErrorCode $errorCode,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $message = $this->errorCode->value;
        parent::__construct($message, $code, $previous);
    }
}
