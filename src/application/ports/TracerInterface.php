<?php

declare(strict_types=1);

namespace app\application\ports;

interface TracerInterface
{
    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, string|int|float|bool> $attributes
     */
    public function startSpan(string $name, array $attributes = []): SpanInterface;

    public function activeSpan(): SpanInterface|null;

    /**
     * @template T
     * @param callable(): T $callback
     * @param array<non-empty-string, string|int|float|bool> $attributes
     * @return T
     */
    public function trace(string $name, callable $callback, array $attributes = []): mixed;

    public function flush(): void;
}
