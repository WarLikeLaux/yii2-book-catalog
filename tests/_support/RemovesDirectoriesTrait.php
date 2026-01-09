<?php

declare(strict_types=1);

namespace tests\_support;

trait RemovesDirectoriesTrait
{
    private function removeDir(string $dir): void
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) || is_link($dir)) {
            if (is_link($dir) || is_file($dir)) {
                @unlink(rtrim($dir, DIRECTORY_SEPARATOR));
            }

            return;
        }

        $realPath = realpath($dir);

        if (
                $realPath === false
                || !$this->isPathAllowed(rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
        ) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $fileinfo */
        foreach ($files as $fileinfo) {
            $path = $fileinfo->getPathname();
            $realEntryPath = realpath($path);

            if (
                    $realEntryPath === false
                    || !str_starts_with(rtrim($realEntryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $realPath)
            ) {
                continue;
            }

            if ($fileinfo->isLink() || !$fileinfo->isDir()) {
                @unlink($path);
            } else {
                @rmdir($path);
            }
        }

        @rmdir(rtrim($dir, DIRECTORY_SEPARATOR));
    }

    private function isPathAllowed(string $realPath): bool
    {
        $allowedBases = [
            realpath(sys_get_temp_dir()),
            realpath(__DIR__ . '/../_output'),
        ];

        foreach ($allowedBases as $base) {
            if ($base === false) {
                continue;
            }

            $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (str_starts_with($realPath, $base)) {
                return true;
            }
        }

        return false;
    }
}
