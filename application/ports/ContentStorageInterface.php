<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\values\FileContent;
use app\domain\values\FileKey;

interface ContentStorageInterface
{
    public function save(FileContent $content): FileKey;

    public function exists(FileKey $key, string $extension = ''): bool;

    public function getUrl(FileKey $key, string $extension = ''): string;

    /**
     * @return iterable<FileKey>
     */
    public function listAllKeys(): iterable;

    public function delete(FileKey $key, string $extension = ''): void;
}
