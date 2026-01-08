<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\common\IdentifiableEntityInterface;

final class Subscription implements IdentifiableEntityInterface
{
    /**
     * Initialize a Subscription entity with an identifier, phone number, and author identifier.
     *
     * Properties are publicly readable and privately writable.
     *
     * @param int|null $id Nullable subscription id; null indicates a new/unsaved subscription.
     * @param string $phone Subscriber phone number.
     * @param int $authorId Identifier of the subscription's author.
     */
    public function __construct(
        public private(set) ?int $id,
        public private(set) string $phone,
        public private(set) int $authorId,
    ) {
    }

    public static function create(string $phone, int $authorId): self
    {
        return new self(null, $phone, $authorId);
    }
}