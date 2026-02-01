<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use app\domain\exceptions\DomainException;
use RuntimeException;
use Throwable;

final class ApplicationException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($this->errorCode, $code, $previous);
    }

    public static function fromDomainException(DomainException $exception): self
    {
        return new self($exception->errorCode->value, $exception->getCode(), $exception);
    }
}
