<?php

declare(strict_types=1);

namespace app\commands;

use app\application\ports\AsyncIdempotencyStorageInterface;
use yii\console\Controller;
use yii\console\ExitCode;

final class IdempotencyController extends Controller
{
    private const int DEFAULT_MAX_AGE_HOURS = 48;

    public function __construct(
        $id,
        $module,
        private readonly AsyncIdempotencyStorageInterface $storage,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionCleanup(int $hours = self::DEFAULT_MAX_AGE_HOURS): int
    {
        $maxAgeSeconds = $hours * 3600;
        $deleted = $this->storage->deleteExpired($maxAgeSeconds);

        $this->stdout("Deleted {$deleted} expired idempotency records.\n");

        return ExitCode::OK;
    }
}
