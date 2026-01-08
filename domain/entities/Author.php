<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\common\IdentifiableEntityInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;

final class Author implements IdentifiableEntityInterface
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
            $this->fio = $trimmed;
        }
    }
    /**
     * Create a new Author instance.
     *
     * @param int|null $id The author's identifier, or `null` for a new/unsaved author.
     * @param string $fio The author's full name; it will be trimmed and validated.
     *
     * @throws \app\exceptions\ValidationException If `$fio` is empty, shorter than MIN_FIO_LENGTH, or longer than MAX_FIO_LENGTH (error codes in DomainErrorCode).
     */

    public function __construct(
        public private(set) ?int $id,
        string $fio,
    ) {
        $this->fio = $fio;
    }

    /**
     * Create a new Author with no id and the given full name.
     *
     * @param string $fio The author's full name.
     * @return self A new Author instance with null id and the provided full name.
     * @throws ValidationException If `$fio` is empty, shorter than MIN_FIO_LENGTH, or longer than MAX_FIO_LENGTH.
     */
    public static function create(string $fio): self
    {
        return new self(null, $fio);
    }

    /**
     * Updates the author's full name (FIO).
     *
     * The value is trimmed and validated against class constraints; assignment fails if the name is empty or its length is less than 2 or greater than 255.
     *
     * @param string $fio The new full name.
     * @throws ValidationException If the provided name is empty, shorter than 2 characters, or longer than 255 characters.
     */
    public function update(string $fio): void
    {
        $this->fio = $fio;
    }
}