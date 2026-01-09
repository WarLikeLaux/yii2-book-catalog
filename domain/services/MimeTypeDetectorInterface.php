<?php

declare(strict_types=1);

namespace app\domain\services;

interface MimeTypeDetectorInterface
{
    public function detect(string $path): string;
}
