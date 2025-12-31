<?php

declare(strict_types=1);

namespace app\application\authors\queries;

use JsonSerializable;

final readonly class AuthorReadDto implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $fio
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'fio' => $this->fio,
        ];
    }
}
