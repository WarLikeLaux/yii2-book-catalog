<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use app\domain\exceptions\DomainException;
use RuntimeException;
use Throwable;

class ApplicationException extends RuntimeException implements FieldAwareExceptionInterface
{
    public function __construct(
        public readonly string $errorCode,
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $field = null,
    ) {
        parent::__construct($this->errorCode, $code, $previous);
    }

    public static function fromDomainException(DomainException $exception): self
    {
        return new self($exception->errorCode->value, $exception->getCode(), $exception);
    }

    public function getField(): ?string
    {
        return $this->field;
    }
}
