<?php

declare(strict_types=1);

namespace app\domain\common;

interface IdentifiableEntityInterface
{
    public ?int $id { get; }
}
