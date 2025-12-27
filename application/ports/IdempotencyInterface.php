<?php

declare(strict_types=1);

namespace app\application\ports;

interface IdempotencyInterface
{
    /** @return array{status_code: int, body: string}|null */
    public function getResponse(string $key): array|null;

    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void;
}
