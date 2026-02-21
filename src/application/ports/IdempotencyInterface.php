<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\IdempotencyRecordDto;

interface IdempotencyInterface
{
    public function getRecord(string $key): IdempotencyRecordDto|null;

    public function saveStarted(string $key, int $ttl): bool;

    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void;
}
