<?php

declare(strict_types=1);

namespace app\application\ports;

interface RequestIdProviderInterface
{
    public function getRequestId(): string;
}
