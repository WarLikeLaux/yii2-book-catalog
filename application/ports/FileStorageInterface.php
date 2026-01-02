<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\TemporaryFile;

interface FileStorageInterface
{
    public function saveTemporary(string $tempPath, string $extension): TemporaryFile;

    public function moveToPermanent(TemporaryFile $file): void;

    public function deleteTemporary(TemporaryFile $file): void;

    public function delete(string $url): void;
}
