<?php

declare(strict_types=1);

namespace app\application\ports;

interface AsyncIdempotencyStorageInterface
{
    public function acquire(string $key): bool;

    public function release(string $key): void;

    public function deleteExpired(int $maxAgeSeconds): int;
}
