<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;

final class Author
{
    private const int MIN_FIO_LENGTH = 2;
    private const int MAX_FIO_LENGTH = 255;

    public function __construct(
        public private(set) ?int $id,
        public private(set) string $fio,
    ) {
        $this->validateFio($fio);
    }

    public static function create(string $fio): self
    {
        return new self(null, $fio);
    }

    private function validateFio(string $fio): void
    {
        $trimmed = trim($fio);

        if ($trimmed === '') {
            throw new ValidationException(DomainErrorCode::AuthorFioEmpty);
        }

        if (mb_strlen($trimmed) < self::MIN_FIO_LENGTH) {
            throw new ValidationException(DomainErrorCode::AuthorFioTooShort);
        }

        if (mb_strlen($trimmed) > self::MAX_FIO_LENGTH) {
            throw new ValidationException(DomainErrorCode::AuthorFioTooLong);
        }
    }

    public function update(string $fio): void
    {
        $this->validateFio($fio);
        $this->fio = $fio;
    }
}
