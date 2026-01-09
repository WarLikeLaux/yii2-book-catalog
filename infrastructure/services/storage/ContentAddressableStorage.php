<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\ports\ContentStorageInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\domain\exceptions\ValidationException;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use yii\helpers\FileHelper;

final readonly class ContentAddressableStorage implements ContentStorageInterface
{
    public function __construct(
        private StorageConfig $config,
    ) {
    }

    public function save(FileContent $content): FileKey
    {
        $key = $content->computeKey();
        $relativePath = $key->getExtendedPath($content->extension);
        $fullPath = $this->resolvePath($relativePath);

        if (file_exists($fullPath)) {
            return $key;
        }

        $dir = dirname($fullPath);
        FileHelper::createDirectory($dir);

        $stream = $content->getStream();
        $target = fopen($fullPath, 'wb');

        if ($target === false) {
            throw new OperationFailedException(DomainErrorCode::FileStorageOperationFailed); // @codeCoverageIgnore
        }

        $bytesCopied = stream_copy_to_stream($stream, $target);

        // @codeCoverageIgnoreStart
        if ($bytesCopied === false) {
            fclose($target);
            throw new OperationFailedException(DomainErrorCode::FileStorageOperationFailed);
        }

        // @codeCoverageIgnoreEnd

        fclose($target);

        return $key;
    }

    public function exists(FileKey $key, string $extension = ''): bool
    {
        $relativePath = $key->getExtendedPath($extension);
        $fullPath = $this->resolvePath($relativePath);

        return file_exists($fullPath);
    }

    public function getUrl(FileKey $key, string $extension = ''): string
    {
        return $this->config->baseUrl . '/' . $key->getExtendedPath($extension);
    }

    public function getModificationTime(FileKey $key, string $extension = ''): int
    {
        $relativePath = $key->getExtendedPath($extension);
        $fullPath = $this->resolvePath($relativePath);
        $mtime = @filemtime($fullPath);

        if ($mtime === false) {
             throw new OperationFailedException(DomainErrorCode::FileStorageOperationFailed); // @codeCoverageIgnore
        }

        return $mtime;
    }

    /**
     * @return iterable<FileKey>
     */
    public function listAllKeys(): iterable
    {
        $basePath = $this->config->basePath;

        if (!is_dir($basePath)) {
            return;
        }

        yield from $this->scanDirectory($basePath);
    }

    public function delete(FileKey $key, string $extension = ''): void
    {
        $relativePath = $key->getExtendedPath($extension);
        $fullPath = $this->resolvePath($relativePath);

        if (!file_exists($fullPath)) {
            return;
        }

        if (!@unlink($fullPath)) {
             throw new OperationFailedException(DomainErrorCode::FileStorageOperationFailed); // @codeCoverageIgnore
        }

        $this->cleanupEmptyDirectories(dirname($fullPath));
    }

    private function resolvePath(string $relativePath): string
    {
        return $this->config->basePath . '/' . $relativePath;
    }

    /**
     * @return Generator<FileKey>
     */
    private function scanDirectory(string $basePath): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue; // @codeCoverageIgnore
            }

            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            try {
                yield new FileKey($filename);
            } catch (ValidationException) {
                continue;
            }
        }
    }

    private function cleanupEmptyDirectories(string $dir): void
    {
        $basePath = $this->config->basePath;

        while ($dir !== $basePath && is_dir($dir)) {
            $files = scandir($dir);

            if ($files === false || count($files) > 2) {
                break; // @codeCoverageIgnore
            }

            if (!@rmdir($dir)) {
                 break; // @codeCoverageIgnore
            }

            $dir = dirname($dir);
        }
    }
}
