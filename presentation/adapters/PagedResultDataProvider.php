<?php

declare(strict_types=1);

namespace app\presentation\adapters;

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
            $pagination = $result->getPagination();
            if ($pagination instanceof Pagination) {
                $pagination->totalCount = $result->getTotalCount();
            }

            $config['pagination'] = $pagination ?? false;
        }

        parent::__construct($config);
    }

    protected function prepareModels(): array
    {
        return $this->result->getModels();
    }

    protected function prepareKeys($models): array
    {
        $keys = [];
        foreach ($models as $index => $model) {
            if (is_object($model) && isset($model->id)) {
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
