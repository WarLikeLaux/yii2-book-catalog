<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\IdempotencyResponseDto;

interface IdempotencyServiceInterface
{
    public function acquireLock(string $key, int $timeout = 0): bool;

    public function releaseLock(string $key): void;

    public function getResponse(string $key): IdempotencyResponseDto|null;

    public function saveResponse(
        string $key,
        int $statusCode,
        mixed $result,
        string|null $redirectUrl,
        int $ttl
    ): void;
}
