<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use Closure;

final readonly class FinfoFunctions
{
    public function __construct(
        public Closure $open,
        public Closure $file,
    ) {
    }

    public static function fromNative(): self
    {
        return new self(
            finfo_open(...),
            finfo_file(...),
        );
    }
}
