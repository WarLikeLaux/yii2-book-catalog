<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use Throwable;

final class NullSpan implements SpanInterface
{
    /**
     * @param non-empty-string $key
     */
    #[\Override]
    public function setAttribute(string $key, string|int|float|bool $value): self
    {
        return $this;
    }

    #[\Override]
    public function setStatus(bool $ok, string $description = ''): self
    {
        return $this;
    }

    #[\Override]
    public function recordException(Throwable $exception): self
    {
        return $this;
    }

    #[\Override]
    public function end(): void
    {
    }
}
