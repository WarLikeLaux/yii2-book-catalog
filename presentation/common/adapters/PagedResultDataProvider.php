<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\application\common\dto\PaginationDto;
use app\application\ports\PagedResultInterface;
use yii\data\BaseDataProvider;
use yii\data\Pagination;

final class PagedResultDataProvider extends BaseDataProvider
{
    public function __construct(
        private readonly PagedResultInterface $result,
        array $config = []
    ) {
        if (!array_key_exists('pagination', $config)) {
            $paginationDto = $result->getPagination();
            if ($paginationDto instanceof PaginationDto) {
                $pagination = new Pagination([
                    'page' => $paginationDto->page - 1,
                    'pageSize' => $paginationDto->pageSize,
                    'totalCount' => $paginationDto->totalCount,
                ]);
            } else {
                $pagination = false;
            }

            $config['pagination'] = $pagination;
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
                $keys[] = $model['id'];
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
}
