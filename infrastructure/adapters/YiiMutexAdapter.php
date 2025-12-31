<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\MutexInterface;
use yii\mutex\Mutex;

final readonly class YiiMutexAdapter implements MutexInterface
{
    public function __construct(
        private Mutex $mutex
    ) {
    }

    public function acquire(string $name, int $timeout = 0): bool
    {
        return $this->mutex->acquire($name, $timeout);
    }

    public function release(string $name): bool
    {
        return $this->mutex->release($name);
    }
}
