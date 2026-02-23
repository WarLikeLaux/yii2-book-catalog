<?php

declare(strict_types=1);

namespace app\application\ports;

use Throwable;

interface SpanInterface
{
    /**
     * @param non-empty-string $key
     */
    public function setAttribute(string $key, string|int|float|bool $value): self;

    public function setStatus(bool $ok, string $description = ''): self;

    public function recordException(Throwable $exception): self;

    public function end(): void;
}
