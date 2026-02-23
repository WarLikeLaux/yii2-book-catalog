<?php

declare(strict_types=1);

namespace app\presentation\common\exceptions;

use RuntimeException;

final class UnexpectedDtoTypeException extends RuntimeException
{
    public function __construct(
        string $expectedClass,
        mixed $actual,
    ) {
        $actualType = get_debug_type($actual);
        parent::__construct("Expected instance of {$expectedClass}, got {$actualType}");
    }
}
