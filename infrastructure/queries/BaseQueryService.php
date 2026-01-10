<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\PagedResultInterface;
use AutoMapper\AutoMapperInterface;
use LogicException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\Connection;

abstract readonly class BaseQueryService
{
    public function __construct(
        protected Connection $db,
        protected AutoMapperInterface $autoMapper,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return PagedResultInterface<T>
     */
    protected function getPagedResult(
        ActiveQueryInterface $query,
        int $page,
        int $pageSize,
        string $dtoClass,
    ): PagedResultInterface {
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->db,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $mapCallback = fn(object $model): object => $this->mapToDto($model, $dtoClass);

        $models = array_map(
            $mapCallback,
            $dataProvider->getModels(),
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages,
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     */
    protected function mapToDto(object $source, string $targetClass): object
    {
        $dto = $this->autoMapper->map($source, $targetClass);

        if (!($dto instanceof $targetClass)) {
            throw new LogicException(sprintf( // @codeCoverageIgnoreStart
                'AutoMapper returned unexpected type: expected %s, got %s',
                $targetClass,
                get_debug_type($dto),
            )); // @codeCoverageIgnoreEnd
        }

        return $dto;
    }

    protected function exists(ActiveQueryInterface $query, mixed $excludeId = null): bool
    {
        if ($excludeId === null) {
            return $query->exists($this->db);
        }

        if (!$query instanceof ActiveQuery) {
            throw new LogicException('Query must be an instance of ActiveQuery to support dynamic primary key exclusion');
        }

        /** @phpstan-ignore-next-line */
        $modelClass = (string)$query->modelClass;

        if (is_a($modelClass, ActiveRecord::class, true)) {
            $primaryKeys = $modelClass::primaryKey();

            if ($primaryKeys === []) {
                throw new LogicException(sprintf('Model %s must have a primary key', get_debug_type($modelClass)));
            }

            $q = clone $query;

            $this->applyExcludeCondition($q, $primaryKeys, $excludeId);

            return $q->exists($this->db);
        }

        return $query->exists($this->db);
    }

    /**
     * @param string[] $primaryKeys
     */
    private function applyExcludeCondition(ActiveQuery $query, array $primaryKeys, mixed $excludeId): void
    {
        if (count($primaryKeys) === 1) {
            $value = is_array($excludeId) ? ($excludeId[$primaryKeys[0]] ?? null) : $excludeId;

            if ($value === null) {
                throw new LogicException('excludeId must contain the primary key');
            }

            $query->andWhere(['<>', $primaryKeys[0], $value]);

            return;
        }

        if (!is_array($excludeId)) {
            throw new LogicException('excludeId must be an array for composite primary keys');
        }

        $condition = ['and'];

        foreach ($primaryKeys as $pk) {
            $condition[] = ['=', $pk, $excludeId[$pk] ?? throw new LogicException("Missing composite PK part: $pk")];
        }

        $query->andWhere(['not', $condition]);
    }
}
