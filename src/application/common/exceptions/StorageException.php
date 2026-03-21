<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use RuntimeException;
use Throwable;

final class StorageException extends RuntimeException
{
    public function __construct(
        public readonly StorageErrorCode $errorCode,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($errorCode->value, $code, $previous);
    }
}
