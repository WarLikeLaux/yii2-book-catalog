<?php

declare(strict_types=1);

namespace app\commands;

use app\application\ports\BookQueryServiceInterface;
use app\application\ports\ContentStorageInterface;
use app\domain\values\FileKey;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

final class StorageController extends Controller
{
    private const int DEFAULT_TTL_HOURS = 24;
    private const array SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', ''];

    public function __construct(
        $id,
        $module,
        private readonly ContentStorageInterface $storage,
        private readonly BookQueryServiceInterface $bookQuery,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionGc(int $ttlHours = self::DEFAULT_TTL_HOURS): int
    {
        $this->stdout(Yii::t('app', 'storage.gc.starting') . "\n");
        $this->stdout(Yii::t('app', 'storage.gc.ttl', ['hours' => $ttlHours]) . "\n\n");

        try {
            $referencedKeys = $this->bookQuery->getReferencedCoverKeys();
        } catch (\Throwable $e) {
            $this->stderr(Yii::t('app', 'storage.gc.error.fetch_keys', ['error' => $e->getMessage()]) . "\n");
            return ExitCode::DATAERR;
        }

        $this->stdout(Yii::t('app', 'storage.gc.found_referenced', ['count' => count($referencedKeys)]) . "\n");

        $referencedKeysMap = array_flip($referencedKeys);
        $orphanCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $ttlSeconds = $ttlHours * 3600;

        try {
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
                    $this->stderr(Yii::t('app', 'storage.gc.error.processing', [
                        'key' => $key->value,
                        'error' => $e->getMessage(),
                    ]) . "\n");
                    $errorCount++;
                }
            }
        } catch (\Throwable $e) {
            $this->stderr(Yii::t('app', 'storage.gc.error.critical', ['error' => $e->getMessage()]) . "\n");
            return ExitCode::IOERR;
        }

        $this->stdout("\n" . Yii::t('app', 'storage.gc.complete') . "\n");
        $this->stdout(Yii::t('app', 'storage.gc.deleted', ['count' => $orphanCount]) . "\n");
        $this->stdout(Yii::t('app', 'storage.gc.skipped', ['count' => $skippedCount]) . "\n");
        $this->stdout(Yii::t('app', 'storage.gc.errors', ['count' => $errorCount]) . "\n");

        return ExitCode::OK;
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

        $this->stdout(Yii::t('app', 'storage.gc.deleted_orphan', ['key' => $key->value]) . "\n");
    }
}
