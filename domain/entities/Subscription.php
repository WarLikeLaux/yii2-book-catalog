<?php

declare(strict_types=1);

namespace app\domain\entities;

final class Subscription
{
    public function __construct(
        private ?int $id,
        private readonly string $phone,
        private readonly int $authorId
    ) {
    }

    public static function create(string $phone, int $authorId): self
    {
        return new self(null, $phone, $authorId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    /**
     * @internal Только для использования репозиторием
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
