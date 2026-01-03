<?php

declare(strict_types=1);

namespace app\domain\entities;

final class Subscription
{
    public private(set) ?int $id = null;

    public function __construct(
        public private(set) string $phone,
        public private(set) int $authorId
    ) {
    }

    public static function create(string $phone, int $authorId): self
    {
        return new self($phone, $authorId);
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
