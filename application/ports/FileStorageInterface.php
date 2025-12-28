<?php

declare(strict_types=1);

namespace app\application\ports;

interface FileStorageInterface
{
    public function save(string $tempPath, string $extension): string;
}
