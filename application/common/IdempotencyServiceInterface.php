<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\IdempotencyRecordDto;

interface IdempotencyServiceInterface
{
    public function acquireLock(string $key, int $timeout = 0): bool;

    public function releaseLock(string $key): void;

    public function startRequest(string $key, int $ttl): bool;

    public function getRecord(string $key): IdempotencyRecordDto|null;

    public function saveResponse(
        string $key,
        int $statusCode,
        mixed $result,
        string|null $redirectUrl,
        int $ttl,
    ): void;
}
