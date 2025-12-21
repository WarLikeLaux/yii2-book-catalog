<?php

declare(strict_types=1);

namespace app\application\common\adapters;

use app\interfaces\QueryResultInterface;
use yii\data\DataProviderInterface;

final class YiiDataProviderAdapter implements QueryResultInterface
{
    public function __construct(
        private readonly DataProviderInterface $dataProvider
    ) {}

    public function getModels(): array
    {
        return $this->dataProvider->getModels();
    }

    public function getTotalCount(): int
    {
        return $this->dataProvider->getTotalCount();
    }

    public function getPagination(): ?object
    {
        return $this->dataProvider->getPagination();
    }

    public function toDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }
}
