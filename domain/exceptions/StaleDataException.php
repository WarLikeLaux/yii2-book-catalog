<?php

declare(strict_types=1);

namespace app\domain\exceptions;

final class StaleDataException extends DomainException
{
    /** @codeCoverageIgnore */
    public function __construct(string $message = 'book.error.stale_data')
    {
        parent::__construct($message);
    }
}
