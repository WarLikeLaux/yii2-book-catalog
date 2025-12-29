<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;

final class NullTracer implements TracerInterface
{
    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, string|int|float|bool> $attributes
     */
    #[\Override]
    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        return new NullSpan();
    }

    #[\Override]
    public function trace(string $name, callable $callback, array $attributes = []): mixed
    {
        return $callback();
    }

    #[\Override]
    public function activeSpan(): SpanInterface|null
    {
        return null;
    }

    #[\Override]
    public function flush(): void
    {
    }
}
