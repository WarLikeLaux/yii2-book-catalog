<?php

declare(strict_types=1);

namespace app\domain\exceptions;

use Throwable;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
class AlreadyExistsException extends DomainException
{
    public function __construct(
        string $message = 'error.entity_already_exists',
        int $code = 409,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
