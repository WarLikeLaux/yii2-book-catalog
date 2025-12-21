<?php

declare(strict_types=1);

namespace app\presentation\adapters;

use app\application\ports\PagedResultInterface;
use yii\data\DataProviderInterface;

final class PagedResultDataProviderFactory
{
    public function create(PagedResultInterface $result): DataProviderInterface
    {
        return new PagedResultDataProvider($result);
    }
}
