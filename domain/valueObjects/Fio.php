<?php

declare(strict_types=1);

namespace app\domain\valueObjects;

final readonly class Fio
{
    private function __construct(
        private string $value
    ) {}

    public static function fromString(string $fio): self
    {
        $trimmed = trim($fio);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('FIO cannot be empty');
        }

        if (strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('FIO cannot exceed 255 characters');
        }

        return new self($trimmed);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
