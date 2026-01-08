<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\ports\ContentStorageInterface;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use yii\helpers\FileHelper;

final readonly class ContentAddressableStorage implements ContentStorageInterface
{
    /**
     * Create a ContentAddressableStorage using the provided storage configuration.
     *
     * @param StorageConfig $config Storage configuration providing `basePath` for filesystem storage and `baseUrl` for constructing public URLs.
     */
    public function __construct(
        private StorageConfig $config,
    ) {
    }

    /**
     * Store the provided content in the content-addressable storage and return its computed key.
     *
     * @param FileContent $content Source content (provides the bytes stream and extension) to be stored.
     * @return FileKey The computed key that identifies the stored content.
     * @throws RuntimeException If the target file cannot be created or if writing the content to disk fails.
     */
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
            throw new RuntimeException('Cannot create file: ' . $fullPath); // @codeCoverageIgnore
        }

        $bytesCopied = stream_copy_to_stream($stream, $target);

        if ($bytesCopied === false) { // @codeCoverageIgnoreStart
            fclose($target);
            throw new RuntimeException('Failed to copy stream to file: ' . $fullPath);
        } // @codeCoverageIgnoreEnd

        fclose($target);

        return $key;
    }

    / **
     * Check whether a content file identified by the given key (and optional extension) exists in storage.
     *
     * @param FileKey $key The content key identifying the file.
     * @param string $extension Optional file extension or variant suffix (without a leading dot) used when computing the storage path.
     * @return bool `true` if a file exists at the computed storage path for the key and extension, `false` otherwise.
     * /
    public function exists(FileKey $key, string $extension = ''): bool
    {
        $relativePath = $key->getExtendedPath($extension);
        $fullPath = $this->resolvePath($relativePath);

        return file_exists($fullPath);
    }

    /**
     * Build the public URL for a stored file key, optionally targeting a specific extension.
     *
     * @param FileKey $key The content's file key.
     * @param string $extension The file extension to resolve (without a leading dot); empty string selects the original content.
     * @return string The full URL formed by concatenating the configured baseUrl and the key's extended path.
     */
    public function getUrl(FileKey $key, string $extension = ''): string
    {
        return $this->config->baseUrl . '/' . $key->getExtendedPath($extension);
    }

    /**
         * Yield every stored FileKey found under the configured base path.
         *
         * If the configured base path does not exist or is not a directory, this method yields nothing.
         *
         * @return iterable<FileKey> Yields `FileKey` instances for each stored file found under the base path.
         */
    public function listAllKeys(): iterable
    {
        $basePath = $this->config->basePath;

        if (!is_dir($basePath)) {
            return;
        }

        yield from $this->scanDirectory($basePath);
    }

    /**
     * Delete the file identified by the given content key and optional extension, and remove any now-empty parent directories up to the storage base path.
     *
     * @param FileKey $key The content key identifying the file.
     * @param string $extension The file extension to delete (without a leading dot); use an empty string for no extension.
     */
    public function delete(FileKey $key, string $extension = ''): void
    {
        $relativePath = $key->getExtendedPath($extension);
        $fullPath = $this->resolvePath($relativePath);

        if (!file_exists($fullPath)) {
            return;
        }

        unlink($fullPath);
        $this->cleanupEmptyDirectories(dirname($fullPath));
    }

    /**
     * Resolve a storage-relative path to a full filesystem path using the configured base path.
     *
     * @param string $relativePath Path relative to the storage base (no leading slash).
     * @return string The resolved filesystem path. 
     */
    private function resolvePath(string $relativePath): string
    {
        return $this->config->basePath . '/' . $relativePath;
    }

    /**
         * Iterates a directory tree and yields FileKey objects for files whose basenames are 64 hexadecimal characters.
         *
         * @param string $basePath Path to the base directory to scan (recursively).
         * @return Generator<FileKey> Generator yielding a FileKey for each matching file.
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

            if (strlen($filename) !== 64 || !ctype_xdigit($filename)) {
                continue;
            }

            yield new FileKey($filename);
        }
    }

    /**
     * Remove empty parent directories starting from the provided directory up to the configured base path.
     *
     * Stops when the current path is not a directory, when a directory contains entries other than `.` and `..`, or when the configured base path is reached.
     *
     * @param string $dir The directory from which to begin removing empty parent directories.
     */
    private function cleanupEmptyDirectories(string $dir): void
    {
        $basePath = $this->config->basePath;

        while ($dir !== $basePath && is_dir($dir)) {
            $files = scandir($dir);

            if ($files === false || count($files) > 2) {
                break; // @codeCoverageIgnore
            }

            rmdir($dir);
            $dir = dirname($dir);
        }
    }
}