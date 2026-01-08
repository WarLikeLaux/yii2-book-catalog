<?php

declare(strict_types=1);

namespace app\commands;

use app\application\ports\ContentStorageInterface;
use app\domain\values\FileKey;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;

final class StorageController extends Controller
{
    private const int DEFAULT_TTL_HOURS = 24;

    /**
     * Create a StorageController configured with its storage backend, database connection, and filesystem path.
     *
     * @param string $id Controller ID.
     * @param \yii\base\Module|null $module The module the controller belongs to, or null for none.
     * @param ContentStorageInterface $storage Storage backend used to list and delete file keys.
     * @param Connection $db Database connection used to query referenced file keys.
     * @param string $storagePath Absolute filesystem path where storage files are located.
     * @param array $config Additional name-value configuration for the controller.
     */
    public function __construct(
        $id,
        $module,
        private readonly ContentStorageInterface $storage,
        private readonly Connection $db,
        private readonly string $storagePath,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Run storage garbage collection, removing orphaned files older than the given TTL.
     *
     * Outputs progress and a summary of deleted and skipped files to stdout.
     *
     * @param int $ttlHours Time-to-live in hours; files newer than this are preserved.
     * @return int ExitCode::OK on success.
     */
    public function actionGc(int $ttlHours = self::DEFAULT_TTL_HOURS): int
    {
        $this->stdout("Starting storage garbage collection...\n");
        $this->stdout("TTL: {$ttlHours} hours\n\n");

        $referencedKeys = $this->getReferencedFileKeys();
        $this->stdout('Found ' . count($referencedKeys) . " referenced files in database.\n");

        $orphanCount = 0;
        $skippedCount = 0;
        $ttlSeconds = $ttlHours * 3600;

        foreach ($this->storage->listAllKeys() as $key) {
            if (in_array($key->value, $referencedKeys, true)) {
                continue;
            }

            if (!$this->isOlderThanTtl($key, $ttlSeconds)) {
                $skippedCount++;
                continue;
            }

            $this->deleteOrphan($key);
            $orphanCount++;
        }

        $this->stdout("\nGarbage collection complete.\n");
        $this->stdout("Deleted: {$orphanCount} orphan files\n");
        $this->stdout("Skipped: {$skippedCount} files (younger than TTL)\n");

        return ExitCode::OK;
    }

    /**
     * Retrieve referenced cover image file keys from the database's books table.
     *
     * @return string[] Array of filename keys (basename without extension) extracted from non-null `cover_url` values.
     */
    private function getReferencedFileKeys(): array
    {
        $urls = $this->db
            ->createCommand('SELECT cover_url FROM books WHERE cover_url IS NOT NULL')
            ->queryColumn();

        return array_map(
            static fn(string $url): string => pathinfo($url, PATHINFO_FILENAME),
            $urls,
        );
    }

    /**
     * Checks whether the most recently modified file variant for the given key is older than the TTL or no variant exists.
     *
     * @param FileKey $key The storage file key to inspect.
     * @param int $ttlSeconds Time-to-live threshold in seconds.
     * @return bool `true` if no file variant exists or the newest variant's modification time is more than `$ttlSeconds` seconds ago, `false` otherwise.
     */
    private function isOlderThanTtl(FileKey $key, int $ttlSeconds): bool
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', ''];
        $maxMtime = null;

        foreach ($extensions as $ext) {
            $path = $this->resolvePath($key, $ext);

            if (!file_exists($path)) {
                continue;
            }

            $mtime = filemtime($path);

            if ($mtime === false || ($maxMtime !== null && $mtime <= $maxMtime)) {
                continue;
            }

            $maxMtime = $mtime;
        }

        return $maxMtime === null || (time() - $maxMtime) > $ttlSeconds;
    }

    /**
     * Delete all known file variants for the given storage key and report the deletion.
     *
     * Removes files for extensions `jpg`, `jpeg`, `png`, `gif`, `webp`, and the no-extension variant,
     * then writes a short status line to stdout indicating which key was deleted.
     *
     * @param FileKey $key The storage file key identifying the orphaned file (filename without extension).
     */
    private function deleteOrphan(FileKey $key): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', ''];

        foreach ($extensions as $ext) {
            $this->storage->delete($key, $ext);
        }

        $this->stdout("  Deleted orphan: {$key->value}\n");
    }

    /**
     * Builds the absolute filesystem path for a storage file key and extension.
     *
     * @param FileKey $key The storage file key used to generate the relative path.
     * @param string $extension File extension without a leading dot (use an empty string for no extension).
     * @return string The absolute path to the file on disk.
     */
    private function resolvePath(FileKey $key, string $extension): string
    {
        return $this->storagePath . '/' . $key->getExtendedPath($extension);
    }
}