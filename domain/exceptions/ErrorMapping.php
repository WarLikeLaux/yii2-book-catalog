<?php

declare(strict_types=1);

namespace app\domain\exceptions;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class ErrorMapping
{
    public function __construct(
        public ErrorType $type,
        public ?string $field = null,
    ) {
    }
}
