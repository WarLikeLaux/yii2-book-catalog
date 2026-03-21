<?php

declare(strict_types=1);

namespace tests\_support;

use app\application\common\dto\SortRequest;
use app\infrastructure\queries\BaseQueryService;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;

final class TestableBaseQueryService extends BaseQueryService
{
    public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
    {
        return $this->exists($query, $excludeId);
    }

    /**
     * @param string[] $allowedFields
     */
    public function checkApplySort(
        ActiveQuery $query,
        ?SortRequest $sort,
        array $allowedFields,
        string $defaultField,
        int $defaultDirection,
    ): void {
        $this->applySortToQuery($query, $sort, $allowedFields, $defaultField, $defaultDirection);
    }
}
