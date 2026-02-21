<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class Phone implements Stringable
{
    private const string E164_PATTERN = '/^\+[1-9]\d{6,14}$/';

    public private(set) string $value;

    public function __construct(string $phone)
    {
        $trimmed = trim($phone);

        if ($trimmed === '') {
            throw new ValidationException(DomainErrorCode::PhoneEmpty);
        }

        if (preg_match(self::E164_PATTERN, $trimmed) !== 1) {
            throw new ValidationException(DomainErrorCode::PhoneInvalidFormat);
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
