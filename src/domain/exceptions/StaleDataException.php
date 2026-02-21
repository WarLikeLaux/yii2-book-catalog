<?php

declare(strict_types=1);

namespace app\domain\exceptions;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
final class StaleDataException extends DomainException
{
    /** @codeCoverageIgnore */
    public function __construct(DomainErrorCode $error)
    {
        parent::__construct($error);
    }
}
