<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\infrastructure\persistence\DatabaseErrorCode;
use yii\db\IntegrityException;

trait DatabaseExceptionHandlerTrait
{
    private function isDuplicateError(IntegrityException $e): bool
    {
        $code = $e->errorInfo[1] ?? null;

        return DatabaseErrorCode::isDuplicate($code);
    }
}
