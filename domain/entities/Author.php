<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;

final class Author
{
    private const int MIN_FIO_LENGTH = 2;
    private const int MAX_FIO_LENGTH = 255;

    // phpcs:disable PSR2.Classes.PropertyDeclaration,Generic.WhiteSpace.ScopeIndent,SlevomatCodingStandard.ControlStructures.BlockControlStructureSpacing
    public private(set) string $fio {
        set {
            $trimmed = trim($value);
            if ($trimmed === '') {
                throw new ValidationException(DomainErrorCode::AuthorFioEmpty);
            }
            if (mb_strlen($trimmed) < self::MIN_FIO_LENGTH) {
                throw new ValidationException(DomainErrorCode::AuthorFioTooShort);
            }
            if (mb_strlen($trimmed) > self::MAX_FIO_LENGTH) {
                throw new ValidationException(DomainErrorCode::AuthorFioTooLong);
            }
            $this->fio = $value;
        }
    }
    // phpcs:enable

    public function __construct(
        public private(set) ?int $id,
        string $fio,
    ) {
        $this->fio = $fio;
    }

    public static function create(string $fio): self
    {
        return new self(null, $fio);
    }

    public function update(string $fio): void
    {
        $this->fio = $fio;
    }
}
