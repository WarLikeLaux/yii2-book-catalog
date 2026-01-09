<?php

declare(strict_types=1);

namespace app\commands;

use app\application\ports\ContentStorageInterface;
use app\domain\values\FileKey;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Query;

final class StorageController extends Controller
{
    private const int DEFAULT_TTL_HOURS = 24;
    private const array SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', ''];

    public function __construct(
        $id,
        $module,
        private readonly ContentStorageInterface $storage,
        private readonly Connection $db,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionGc(int $ttlHours = self::DEFAULT_TTL_HOURS): int
    {
        $this->stdout("Starting storage garbage collection...\n");
        $this->stdout("TTL: {$ttlHours} hours\n\n");

        $referencedKeys = $this->getReferencedFileKeys();
        $this->stdout('Found ' . count($referencedKeys) . " referenced files in database.\n");

        $referencedKeysMap = array_flip($referencedKeys);
        $orphanCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $ttlSeconds = $ttlHours * 3600;

        foreach ($this->storage->listAllKeys() as $key) {
            try {
                if (isset($referencedKeysMap[$key->value])) {
                    continue;
                }

                if (!$this->isOlderThanTtl($key, $ttlSeconds)) {
                    $skippedCount++;
                    continue;
                }

                $this->deleteOrphan($key);
                $orphanCount++;
            } catch (\Throwable $e) {
                $this->stderr("Error processing file key {$key->value}: " . $e->getMessage() . "\n");
                $errorCount++;
            }
        }

        $this->stdout("\nGarbage collection complete.\n");
        $this->stdout("Deleted: {$orphanCount} orphan files\n");
        $this->stdout("Skipped: {$skippedCount} files (younger than TTL)\n");
        $this->stdout("Errors:  {$errorCount}\n");

        return ExitCode::OK;
    }

    /**
     * @return string[]
     */
    private function getReferencedFileKeys(): array
    {
        $urls = (new Query())
            ->select('cover_url')
            ->from('books')
            ->where(['IS NOT', 'cover_url', null])
            ->column($this->db);

        return array_map(
            static fn(string $url): string => pathinfo($url, PATHINFO_FILENAME),
            $urls,
        );
    }

    private function isOlderThanTtl(FileKey $key, int $ttlSeconds): bool
    {
        $maxMtime = null;

        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            try {
                $mtime = $this->storage->getModificationTime($key, $ext);
            } catch (\RuntimeException) {
                continue;
            }

            if ($maxMtime !== null && $mtime <= $maxMtime) {
                continue;
            }

            $maxMtime = $mtime;
        }

        return $maxMtime === null || (time() - $maxMtime) > $ttlSeconds;
    }

    private function deleteOrphan(FileKey $key): void
    {
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $this->storage->delete($key, $ext);
        }

        $this->stdout("  Deleted orphan: {$key->value}\n");
    }
}
