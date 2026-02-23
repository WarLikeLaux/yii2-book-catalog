<?php

declare(strict_types=1);

namespace app\domain\exceptions;

use Throwable;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
final class AlreadyExistsException extends DomainException
{
    public function __construct(
        DomainErrorCode $error = DomainErrorCode::EntityAlreadyExists,
        int $code = 409,
        ?Throwable $previous = null,
    ) {
        parent::__construct($error, $code, $previous);
    }
}
