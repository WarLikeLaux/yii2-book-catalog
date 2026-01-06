<?php

declare(strict_types=1);

namespace app\domain\exceptions;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
final class BusinessRuleException extends DomainException
{
    public function __construct(
        DomainErrorCode $domainError,
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($domainError, $code, $previous);
    }
}
