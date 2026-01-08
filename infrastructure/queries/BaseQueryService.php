<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\PagedResultInterface;
use AutoMapper\AutoMapperInterface;
use LogicException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\db\Connection;

abstract readonly class BaseQueryService
{
    /**
     * Initialize the service with required dependencies.
     *
     * Stores the provided database connection and automapper for use by query operations.
     */
    public function __construct(
        protected Connection $db,
        protected AutoMapperInterface $autoMapper,
    ) {
    }

    /**
     * Retrieve a paged result from the given query and map each record to instances of the specified DTO class.
     *
     * @template T of object
     * @param ActiveQueryInterface $query The query used to fetch records.
     * @param int $page The 1-based page number.
     * @param int $pageSize Number of items per page.
     * @param class-string<T> $dtoClass The DTO class to map each record to.
     * @return PagedResultInterface<T> Paged result containing an array of mapped DTOs, the total record count, and pagination metadata.
     */
    protected function getPagedResult(
        ActiveQueryInterface $query,
        int $page,
        int $pageSize,
        string $dtoClass,
    ): PagedResultInterface {
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->db,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            fn(object $model): object => $this->mapToDto($model, $dtoClass),
            $dataProvider->getModels(),
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = $pageSize > 0 ? (int)ceil($totalCount / $pageSize) : 0;

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
     * Map a source object to an instance of the specified DTO class.
     *
     * @template T of object
     * @param object $source The source object to map from.
     * @param class-string<T> $targetClass Fully-qualified class name of the target DTO type.
     * @return T The mapped DTO instance.
     * @throws LogicException If the mapper returns a value that is not an instance of `$targetClass`.
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

    /**
     * Checks whether any record matches the given query, optionally excluding a specific id.
     *
     * @param ActiveQueryInterface $query The query to evaluate.
     * @param int|null $excludeId If provided, ignores records with this `id` value when checking existence.
     * @return bool `true` if at least one matching record exists, `false` otherwise.
     */
    protected function exists(ActiveQueryInterface $query, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists($this->db);
    }
}