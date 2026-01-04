<?php

declare(strict_types=1);

namespace app\domain\entities;

final class Subscription
{
    public function __construct(
        public private(set) ?int $id,
        public private(set) string $phone,
        public private(set) int $authorId
    ) {
    }

    public static function create(string $phone, int $authorId): self
    {
        return new self(null, $phone, $authorId);
    }
}
