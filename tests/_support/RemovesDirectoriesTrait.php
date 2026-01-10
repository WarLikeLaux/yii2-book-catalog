<?php

declare(strict_types=1);

namespace tests\_support;

trait RemovesDirectoriesTrait
{
    private function removeDir(string $dir): void
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) || is_link($dir)) {
            $this->deleteFileOrLink(rtrim($dir, DIRECTORY_SEPARATOR));
            return;
        }

        $this->deleteDirectoryRecursively($dir);
    }

    private function deleteFileOrLink(string $path): void
    {
        if (!is_link($path) && !is_file($path)) {
            return;
        }

        $realPath = realpath($path);

        if ($realPath === false) {
            return;
        }

        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!$this->isPathAllowed($normalizedPath)) {
            return;
        }

        if (!unlink($path)) {
            throw new DirectoryRemovalException("Failed to delete file: {$path}");
        }
    }

    private function deleteDirectoryRecursively(string $dir): void
    {
        $realPath = realpath($dir);

        if ($realPath === false) {
            return;
        }

        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!$this->isPathAllowed($normalizedPath)) {
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

            if ($realEntryPath === false) {
                $this->deleteBrokenSymlink($path, $fileinfo, $normalizedPath);
                continue;
            }

            $normalizedEntryPath = rtrim($realEntryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (!str_starts_with($normalizedEntryPath, $normalizedPath)) {
                continue;
            }

            if ($fileinfo->isLink() || !$fileinfo->isDir()) {
                if (!unlink($path)) {
                    throw new DirectoryRemovalException("Failed to delete file: {$path}");
                }

                continue;
            }

            if (!rmdir($path)) {
                throw new DirectoryRemovalException("Failed to delete directory: {$path}");
            }
        }

        if (!rmdir(rtrim($dir, DIRECTORY_SEPARATOR))) {
            throw new DirectoryRemovalException("Failed to delete directory: {$dir}");
        }
    }

    private function deleteBrokenSymlink(string $path, \SplFileInfo $fileinfo, string $normalizedPath): void
    {
        if (!$fileinfo->isLink()) {
            return;
        }

        $normalizedEntryPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!str_starts_with($normalizedEntryPath, $normalizedPath)) {
            return;
        }

        if (!unlink($path)) {
            throw new DirectoryRemovalException("Failed to delete symlink: {$path}");
        }
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
