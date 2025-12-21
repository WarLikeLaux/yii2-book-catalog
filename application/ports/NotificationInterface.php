<?php

declare(strict_types=1);

namespace app\application\ports;

interface NotificationInterface
{
    public function success(string $message): void;

    public function error(string $message): void;

    public function info(string $message): void;

    public function warning(string $message): void;
}
