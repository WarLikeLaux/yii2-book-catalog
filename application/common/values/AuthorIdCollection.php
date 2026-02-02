<?php

declare(strict_types=1);

namespace app\application\common\values;

final readonly class AuthorIdCollection
{
    /**
     * @param array<int> $ids
     */
    private function __construct(private array $ids)
    {
    }

    /**
     * @param array<int> $ids
     */
    public static function fromArray(array $ids): self
    {
        return new self(self::normalize($ids));
    }

    public static function fromMixed(mixed $value): self
    {
        if (!is_array($value)) {
            $value = $value === null ? [] : [$value];
        }

        return new self(self::normalize($value));
    }

    /**
     * @param array<mixed> $value
     * @return array<int>
     */
    private static function normalize(array $value): array
    {
        $normalized = [];

        foreach ($value as $rawId) {
            if (!is_int($rawId) && !is_string($rawId)) {
                continue;
            }

            if (is_string($rawId) && !is_numeric($rawId)) {
                continue;
            }

            $id = (int) $rawId;

            if ($id <= 0) {
                continue;
            }

            $normalized[] = $id;
        }

        return $normalized;
    }

    /**
     * @return array<int>
     */
    public function toArray(): array
    {
        return $this->ids;
    }
}
