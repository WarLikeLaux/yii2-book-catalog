<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\common\IdentifiableEntityInterface;

final class Subscription implements IdentifiableEntityInterface
{
    private function __construct(
        public private(set) ?int $id,
        public private(set) string $phone,
        public private(set) int $authorId,
    ) {
    }

    public static function create(string $phone, int $authorId): self
    {
        return new self(null, $phone, $authorId);
    }

    public static function reconstitute(int $id, string $phone, int $authorId): self
    {
        return new self($id, $phone, $authorId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
