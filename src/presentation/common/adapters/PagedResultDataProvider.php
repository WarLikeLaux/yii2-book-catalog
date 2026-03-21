<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\application\common\dto\PaginationDto;
use app\application\ports\PagedResultInterface;
use yii\data\BaseDataProvider;
use yii\data\Pagination;
use yii\data\Sort;

final class PagedResultDataProvider extends BaseDataProvider
{
    /**
     * @param string[] $sortAttributes
     */
    public function __construct(
        private readonly PagedResultInterface $result,
        array $sortAttributes = [],
        array $config = [],
    ) {
        if (!array_key_exists('pagination', $config)) {
            $paginationDto = $result->getPagination();

            if ($paginationDto instanceof PaginationDto) {
                $pagination = new Pagination([
                    'page' => $paginationDto->page - 1,
                    'pageSize' => $paginationDto->limit,
                    'totalCount' => $paginationDto->totalCount,
                ]);
            } else {
                $pagination = false;
            }

            $config['pagination'] = $pagination;
        }

        if ($sortAttributes !== [] && !array_key_exists('sort', $config)) {
            $config['sort'] = $this->buildSort($sortAttributes);
        }

        parent::__construct($config);
    }

    /**
     * @return array<mixed>
     */
    protected function prepareModels(): array
    {
        return $this->result->getModels();
    }

    /**
     * @param array<mixed> $models
     * @return array<int|string>
     */
    protected function prepareKeys($models): array
    {
        /** @var array<int|string> $keys */
        $keys = [];

        foreach ($models as $index => $model) {
            if (is_object($model) && property_exists($model, 'id')) {
                /** @var object{id: int|string} $model */
                $keys[] = $model->id;
                continue;
            }

            if (is_array($model) && array_key_exists('id', $model)) {
                /** @var int|string $id */
                $id = $model['id'];
                $keys[] = $id;
                continue;
            }

            $keys[] = $index;
        }

        return $keys;
    }

    protected function prepareTotalCount(): int
    {
        return $this->result->getTotalCount();
    }

    /**
     * @param string[] $attributes
     */
    private function buildSort(array $attributes): Sort
    {
        $sortAttributes = [];

        foreach ($attributes as $attr) {
            $sortAttributes[$attr] = [
                'asc' => [$attr => SORT_ASC],
                'desc' => [$attr => SORT_DESC],
            ];
        }

        return new Sort([
            'attributes' => $sortAttributes,
            'enableMultiSort' => false,
        ]);
    }
}
