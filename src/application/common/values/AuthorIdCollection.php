<?php

declare(strict_types=1);

namespace app\application\common\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\AuthorId;

final readonly class AuthorIdCollection
{
    /**
     * @param AuthorId[] $ids
     */
    private function __construct(private array $ids)
    {
    }

    /**
     * @param array<mixed> $ids
     */
    public static function fromArray(array $ids): self
    {
        $validated = [];
        $seen = [];

        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
            }

            if ($id <= 0) {
                throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
            }

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $validated[] = new AuthorId($id);
        }

        return new self($validated);
    }

    /**
     * @return AuthorId[]
     */
    public function toArray(): array
    {
        return $this->ids;
    }

    /**
     * @return int[]
     */
    public function toIntArray(): array
    {
        return array_map(static fn(AuthorId $id): int => $id->value, $this->ids);
    }
}
