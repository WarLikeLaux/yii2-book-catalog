<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;
use Override;

final class NullTracer implements TracerInterface
{
    /**
     * @param non-empty-string $_name
     * @param array<non-empty-string, string|int|float|bool> $_attributes
     */
    #[Override]
    public function startSpan(string $_name, array $_attributes = []): SpanInterface
    {
        return new NullSpan();
    }

    /**
     * @param array<string, mixed> $_attributes
     */
    #[Override]
    public function trace(string $_name, callable $callback, array $_attributes = []): mixed
    {
        return $callback();
    }

    #[Override]
    public function activeSpan(): SpanInterface|null
    {
        return null;
    }

    #[Override]
    public function flush(): void
    {
    }
}
