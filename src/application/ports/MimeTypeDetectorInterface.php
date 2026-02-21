<?php

declare(strict_types=1);

namespace app\application\ports;

interface MimeTypeDetectorInterface
{
    public function detect(string $path): string;
}
