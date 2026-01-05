<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\infrastructure\persistence\DatabaseErrorCode;
use yii\db\IntegrityException;

trait DatabaseExceptionHandlerTrait
{
    private function isDuplicateError(IntegrityException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->errorInfo[0] ?? null;

        return DatabaseErrorCode::isDuplicate($driverCode)
            || DatabaseErrorCode::isDuplicate($sqlState);
    }
}
