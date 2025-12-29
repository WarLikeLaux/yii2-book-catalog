<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\exceptions\DomainException;

final class Author
{
    private const int MIN_FIO_LENGTH = 2;
    private const int MAX_FIO_LENGTH = 255;

    public function __construct(
        private ?int $id,
        private string $fio
    ) {
        $this->validateFio($fio);
    }

    private function validateFio(string $fio): void
    {
        $trimmed = trim($fio);

        if ($trimmed === '') {
            throw new DomainException('author.error.fio_empty');
        }

        if (mb_strlen($trimmed) < self::MIN_FIO_LENGTH) {
            throw new DomainException('author.error.fio_too_short');
        }

        if (mb_strlen($trimmed) > self::MAX_FIO_LENGTH) {
            throw new DomainException('author.error.fio_too_long');
        }
    }

    public static function create(string $fio): self
    {
        return new self(null, $fio);
    }

    public function update(string $fio): void
    {
        $this->validateFio($fio);
        $this->fio = $fio;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFio(): string
    {
        return $this->fio;
    }

    /**
     * @internal Только для использования репозиторием
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
