<?php

declare(strict_types=1);

namespace app\domain\entities;

final class Author
{
    public function __construct(
        private ?int $id,
        private string $fio
    ) {
    }

    public static function create(string $fio): self
    {
        return new self(null, $fio);
    }

    public function update(string $fio): void
    {
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
