<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class IdempotencyResponseDto
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public int $statusCode,
        public array $data,
        public string|null $redirectUrl = null,
    ) {
    }
}
