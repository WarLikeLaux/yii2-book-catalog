<?php

declare(strict_types=1);

namespace tests\_support;

trait RemovesDirectoriesTrait
{
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scan = scandir($dir);

        if ($scan === false) {
            return;
        }

        $files = array_diff($scan, ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
