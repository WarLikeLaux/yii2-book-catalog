<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use Override;
use Throwable;

final readonly class NullSpan implements SpanInterface
{
    /**
     * @param non-empty-string $_key
     */
    #[Override]
    public function setAttribute(string $_key, string|int|float|bool $_value): self
    {
        return $this;
    }

    #[Override]
    public function setStatus(bool $_ok, string $_description = ''): self
    {
        return $this;
    }

    #[Override]
    public function recordException(Throwable $_exception): self
    {
        return $this;
    }

    #[Override]
    public function end(): void
    {
    }
}
