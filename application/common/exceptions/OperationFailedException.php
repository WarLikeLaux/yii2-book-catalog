<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use Throwable;

final class OperationFailedException extends ApplicationException
{
    public function __construct(
        string $errorCode,
        ?string $field = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($errorCode, $code, $previous, $field);
    }
}
