<?php

declare(strict_types=1);

namespace tests\_support;

trait RemovesDirectoriesTrait
{
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir) || is_link($dir)) {
            if (is_link($dir) || is_file($dir)) {
                @unlink($dir);
            }

            return;
        }

        $realPath = realpath($dir);
        $allowedBases = [
            realpath(sys_get_temp_dir()),
            realpath(__DIR__ . '/../_output'),
        ];

        $isAllowed = false;

        foreach ($allowedBases as $base) {
            if ($base !== false && $realPath !== false && str_starts_with($realPath, $base)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($dir);
    }
}
