<?php

declare(strict_types=1);

namespace app\application\common\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;

final readonly class AuthorIdCollection
{
    /**
     * @param array<int> $ids
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

        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
            }

            if ($id <= 0) {
                throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
            }

            $validated[] = $id;
        }

        return new self(array_values(array_unique($validated)));
    }

    /**
     * @return array<int>
     */
    public function toArray(): array
    {
        return $this->ids;
    }
}
