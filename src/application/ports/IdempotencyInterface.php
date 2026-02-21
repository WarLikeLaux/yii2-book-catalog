<?php

declare(strict_types=1);

namespace app\application\ports;

interface IdempotencyInterface
{
    /** @return array{status: string, status_code: int|null, body: string|null}|null */
    public function getRecord(string $key): array|null;

    public function saveStarted(string $key, int $ttl): bool;

    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void;
}
