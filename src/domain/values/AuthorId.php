<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class AuthorId implements Stringable
{
    public private(set) int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
        }

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
