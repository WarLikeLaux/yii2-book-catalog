<?php

declare(strict_types=1);

namespace tests\_support;

use app\infrastructure\queries\BaseQueryService;
use yii\db\ActiveQueryInterface;

final class TestableBaseQueryService extends BaseQueryService
{
    public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
    {
        return $this->exists($query, $excludeId);
    }
}
