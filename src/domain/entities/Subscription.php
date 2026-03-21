<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\common\IdentifiableEntityInterface;
use app\domain\values\AuthorId;
use app\domain\values\Phone;

final class Subscription implements IdentifiableEntityInterface
{
    private function __construct(
        public private(set) ?int $id,
        public private(set) Phone $phone,
        public private(set) AuthorId $authorId,
    ) {
    }

    public static function create(Phone $phone, AuthorId $authorId): self
    {
        return new self(null, $phone, $authorId);
    }

    public static function reconstitute(int $id, Phone $phone, AuthorId $authorId): self
    {
        return new self($id, $phone, $authorId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
