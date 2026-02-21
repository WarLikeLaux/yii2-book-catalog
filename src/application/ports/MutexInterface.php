<?php

declare(strict_types=1);

namespace app\application\ports;

interface MutexInterface
{
    public function acquire(string $name, int $timeout = 0): bool;

    public function release(string $name): bool;
}
