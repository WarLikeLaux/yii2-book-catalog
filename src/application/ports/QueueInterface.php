<?php

declare(strict_types=1);

namespace app\application\ports;

interface QueueInterface
{
    public function push(object $job): void;
}
