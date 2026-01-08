<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\values\FileContent;
use app\domain\values\FileKey;

interface ContentStorageInterface
{
    /**
 * Stores the provided file content and returns a key that identifies the stored content.
 *
 * @param FileContent $content The file content to store.
 * @return FileKey The key identifying the stored content.
 */
public function save(FileContent $content): FileKey;

    /**
 * Checks whether content identified by the given FileKey exists, optionally for a specific extension.
 *
 * @param FileKey $key The identifier of the stored content.
 * @param string $extension Optional file extension or variant to consider; pass an empty string to ignore extensions.
 * @return bool `true` if content exists for the provided key and extension, `false` otherwise.
 */
public function exists(FileKey $key, string $extension = ''): bool;

    /**
 * Get the public URL for the content identified by the given FileKey.
 *
 * @param FileKey $key The identifier of the stored content.
 * @param string $extension Optional file extension or variant to resolve a specific rendition.
 * @return string The URL for the requested content.
 */
public function getUrl(FileKey $key, string $extension = ''): string;

    /**
 * List all FileKey objects present in the storage.
 *
 * @return iterable<FileKey> An iterable that yields each stored FileKey.
 */
    public function listAllKeys(): iterable;

    / **
 * Delete the stored content identified by the given file key.
 *
 * @param FileKey $key The identifier of the content to delete.
 * @param string $extension Optional file extension to target a specific variant; when empty, delete all variants.
 */
public function delete(FileKey $key, string $extension = ''): void;
}