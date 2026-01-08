<?php

declare(strict_types=1);

namespace app\commands;

use app\application\ports\AsyncIdempotencyStorageInterface;
use yii\console\Controller;
use yii\console\ExitCode;

final class IdempotencyController extends Controller
{
    private const int DEFAULT_MAX_AGE_HOURS = 48;

    / **
     * Create a new IdempotencyController and attach the idempotency storage.
     *
     * @param string $id Controller ID.
     * @param \yii\base\Module $module The module that this controller belongs to.
     * @param AsyncIdempotencyStorageInterface $storage Storage used to delete expired idempotency records.
     * @param array $config Controller configuration.
     */
    public function __construct(
        $id,
        $module,
        private readonly AsyncIdempotencyStorageInterface $storage,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Remove idempotency records older than a given age.
     *
     * @param int $hours The maximum age in hours of records to delete (defaults to DEFAULT_MAX_AGE_HOURS).
     * @return int Exit code: `ExitCode::OK` on successful cleanup.
     */
    public function actionCleanup(int $hours = self::DEFAULT_MAX_AGE_HOURS): int
    {
        $maxAgeSeconds = $hours * 3600;
        $deleted = $this->storage->deleteExpired($maxAgeSeconds);

        $this->stdout("Deleted {$deleted} expired idempotency records.\n");

        return ExitCode::OK;
    }
}